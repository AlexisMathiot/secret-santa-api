# Projet Symfony avec Docker

Ce projet est configuré pour exécuter une application Symfony à l'aide de Docker.

## Configuration requise

Assurez-vous d'avoir les outils suivants installés sur votre système :

- Docker
- Docker Compose

## Installation

1. Clonez ce dépôt dans votre répertoire local :

    ```bash
    git clone https://github.com/Alexis3386/secret-santa-api
    ```

2. Accédez au répertoire du projet :

    ```bash
    cd secret-santa-api
    ```
3. Créer les containers docker et les lancer

   ```bash
   docker-compose build
   docker-compose up
   ```
4. Installez les dépendances Symfony :

   Accédez au conteneur PHP en exécutant la commande suivante :

    ```bash
    docker exec -it php /bin/bash
    ```

   À l'intérieur du conteneur, installez les dépendances avec Composer :

    ```bash
    composer install
    ```
5. Copier le fichier .env dans un fichier .env.local
   Mettre dans le ficher .env.local l'url vers la base de donnée qui sera :
   ```
   DATABASE_URL="mysql://symfony:pass@database/symfony?serverVersion=8.0.35&charset=utf8mb4"
   ```
6. Créer la structure de la base de donnée et charger les fictures
   ```bash
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

7. Copier dans le fichier .env.local la configuration pour JWT
   ```
   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
   JWT_PASSPHRASE=
   ```
   Mettre ce que vous voulez comme passphrase
   documentation de LexikJWTAuthenticationBundle :
   https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst#installation

8. Générer les clées ssl pour JWT token
   À l'intérieur du conteneur, installez les dépendances avec Composer :

    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```
9. Si tout c'est bien passé vous pouvez envoyer des requêtes sur http://localhost:8080 !

10. Arréter les containers docker avec
   ```bash
    docker-compose down
   ```

