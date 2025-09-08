-- Créer les deux bases si elles n'existent pas
CREATE DATABASE IF NOT EXISTS symfony;
CREATE DATABASE IF NOT EXISTS symfony_test;

-- Donner les droits à l'utilisateur "symfony"
GRANT ALL PRIVILEGES ON symfony.* TO 'symfony'@'%';
GRANT ALL PRIVILEGES ON symfony_test.* TO 'symfony'@'%';

FLUSH PRIVILEGES;
