<?php
// tests/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . "/vendor/autoload.php";

if (file_exists(dirname(__DIR__) . "/config/bootstrap.php")) {
    require dirname(__DIR__) . "/config/bootstrap.php";
} elseif (method_exists(Dotenv::class, "bootEnv")) {
    $dotenv = new Dotenv();
    $dotenv->bootEnv(dirname(__DIR__) . "/.env");
}

// Optionnel : Supprimer la base de test avant de commencer
if (file_exists(dirname(__DIR__) . "/var/data_test.db")) {
    unlink(dirname(__DIR__) . "/var/data_test.db");
}
