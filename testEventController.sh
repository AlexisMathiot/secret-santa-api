#!/bin/bash

# Variables d'environnement
export BASE_URL="http://localhost:8080" # Ajustez selon votre config
export TOKEN="your_jwt_token_here"      # Remplacez par votre token JWT

CONTAINER_PHP="php"

# Vérifier que Docker est lancé
if ! docker info > /dev/null 2>&1; then
  echo "Docker ne semble pas démarré. Veuillez lancer Docker."
  exit 1
fi

# Vérifier que le conteneur PHP est en cours
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_PHP}$"; then
  echo "Le conteneur PHP (${CONTAINER_PHP}) n'est pas démarré. Veuillez exécuter 'docker compose up -d'."
  exit 1
fi

# Méthode 1: Via une commande Symfony (recommandée)
echo "🔍 Vérification de l'environnement Symfony..."
KERNEL_ENV=$(docker exec php bash -c "cd /var/www && php bin/console debug:container --env=test --parameter=kernel.environment 2>/dev/null | grep -oP '(?<=: \").*(?=\")' || echo 'test'")

if [ -z "$KERNEL_ENV" ]; then
    # Fallback si la commande précédente échoue
    KERNEL_ENV=$(docker exec php bash -c "cd /var/www && APP_ENV=test php -r 'echo \$_ENV[\"APP_ENV\"] ?? \"test\";'")
fi

echo "KERNEL_ENV='$KERNEL_ENV'"

if [ "$KERNEL_ENV" != "test" ]; then
    echo "⚠️  L'environnement Symfony n'est pas 'test' (actuellement: $KERNEL_ENV)"
    echo "   Interruption du script pour éviter de wipe la base de dev."
    exit 1
fi

echo "✅ Environnement Symfony = test, on peut réinitialiser la base de test."

# 1. Reset database
docker exec -it php bash -c "
export APP_ENV=test;
php bin/console doctrine:database:drop --force;
php bin/console doctrine:database:create;
php bin/console doctrine:schema:create;
php bin/console doctrine:fixtures:load --no-interaction
"

# Login pour obtenir le token (exemple générique)
TOKEN=$(xh POST $BASE_URL/api/login_check email=admin@santaapi.com password=password | jq -r '.token') && echo "Token: $TOKEN"

# GET /api/events
echo "test liste des évènements"
if xh GET $BASE_URL/api/events "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi

# GET /api/events/{id}
echo "test détails des évènements"
if xh GET $BASE_URL/api/events/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi

# GET /api/events/set-santa/{id}
echo "assigner les pères noels"
if xh GET $BASE_URL/api/events/set-santa/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi

# POST /api/events
echo "créer un évènement"
if xh POST $BASE_URL/api/events "Authorization:Bearer $TOKEN" \
  name="Mon événement de Noël" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi

# PUT /api/events/{id} - Modifier un événement
echo "modifier un événement"
if xh PUT $BASE_URL/api/events/1 "Authorization:Bearer $TOKEN" \
  name="Événement modifié" \
  description="Description modifiée" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""

# GET /api/events/add/{userId}/{eventId} - Ajouter un utilisateur à un événement
echo "ajouter un utilisateur à un événement"
if xh GET $BASE_URL/api/events/add/2/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""

# GET /api/events/organizer/{userId}/{eventId} - Changer l'organisateur
echo "changer l'organisateur d'un événement"
if xh GET $BASE_URL/api/events/organizer/2/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""

# GET /api/events/users-to-invit - Liste des utilisateurs invitables
echo "liste des utilisateurs invitables"
if xh GET $BASE_URL/api/events/users-to-invit "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""

# POST /api/events/invitations/{fromUserId}/{toUserId}/{eventId} - Envoyer une invitation
echo "envoyer une invitation par email"
if xh POST $BASE_URL/api/events/invitations/1/2/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""

# GET /api/events/remove/{userId}/{eventId} - Retirer un utilisateur d'un événement
echo "retirer un utilisateur d'un événement"
if xh GET $BASE_URL/api/events/remove/2/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""

# DELETE /api/events/{id} - Supprimer un événement (à faire en dernier)
echo "supprimer un événement"
if xh DELETE $BASE_URL/api/events/1 "Authorization:Bearer $TOKEN" --check-status --quiet > /dev/null 2>&1; then
    echo "✅ SUCCÈS"
else
    echo "❌ ÉCHEC"
fi
echo ""
