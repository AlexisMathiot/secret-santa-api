<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

/**
 * Classe de base utilisant DAMA/DoctrineTestBundle pour gérer automatiquement
 * les transactions de test sans avoir besoin de reset manuel de la base
 */
abstract class DamaWebTestCase extends WebTestCase
{
    protected $client;
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp(); // ⚠️ obligatoire

        $this->client = static::createClient();
        $this->client->catchExceptions(true);

        $this->entityManager = static::getContainer()
            ->get("doctrine")
            ->getManager();
    }

    protected function authenticate(
        string $email = "admin@santaapi.com",
        string $password = "password",
    ): string {
        $this->client->request(
            "POST",
            "/api/login_check",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json",
            ],
            json_encode([
                "email" => $email,
                "password" => $password,
            ]),
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        return $data["token"];
    }

    protected function makeAuthenticatedRequest(
        string $method,
        string $uri,
        array $data = [],
        string $token = null,
    ): void {
        $authToken = $token ?? $this->authenticate();

        $headers = [
            "HTTP_AUTHORIZATION" => "Bearer " . $authToken,
            "CONTENT_TYPE" => "application/json",
        ];

        $content = empty($data) ? null : json_encode($data);
        $this->client->request($method, $uri, [], [], $headers, $content);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Avec DAMA, le cleanup est automatique
        $this->entityManager = null;
    }
}
