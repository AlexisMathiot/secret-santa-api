<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Tests\DamaWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EventControllerTest extends DamaWebTestCase
{
    private string $authToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Charger les données de test
        $this->loadTestData();

        // S'authentifier
        $this->authToken = $this->authenticate();
    }

    private function loadTestData(): void
    {
        // Créer des utilisateurs de test
        $admin = new User();
        $admin
            ->setEmail("admin@santaapi.com")
            ->setUsername("Admin")
            ->setPassword(password_hash("password", PASSWORD_BCRYPT))
            ->setRoles(["ROLE_ADMIN"]);

        $user1 = new User();
        $user1
            ->setEmail("user1@example.com")
            ->setUsername("User1")
            ->setPassword(password_hash("password", PASSWORD_BCRYPT))
            ->setRoles(["ROLE_USER"]);

        $user2 = new User();
        $user2
            ->setEmail("user2@example.com")
            ->setUsername("User2")
            ->setPassword(password_hash("password", PASSWORD_BCRYPT))
            ->setRoles(["ROLE_USER"]);

        // Créer un événement de test
        $event = new Event();
        $event->setName("Test Event")->setOrganizer($admin);

        $this->entityManager->persist($admin);
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    public function testGetEventsList(): void
    {
        $this->makeAuthenticatedRequest(
            "GET",
            "/api/events",
            [],
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
        );

        $response = $this->client->getResponse();

        $response = $this->client->getResponse()->getContent();

        $data = json_decode($response, true);
        $this->assertIsArray($data);
    }

    public function testGetEventDetails(): void
    {
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy([]);
        $eventId = $event->getId();

        $this->makeAuthenticatedRequest(
            "GET",
            "/api/events/" . $eventId,
            [],
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("id", $data);
        $this->assertEquals($eventId, $data["id"]);
    }

    public function testCreateEvent(): void
    {
        $eventData = [
            "name" => "Mon événement de Noël",
        ];

        $this->makeAuthenticatedRequest(
            "POST",
            "/api/events",
            $eventData,
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_CREATED,
            $this->client->getResponse()->getStatusCode(),
        );

        // Vérifier que l'événement a été créé en base
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy(["name" => "Mon événement de Noël"]);
        $this->assertNotNull($event);
    }

    public function testUpdateEvent(): void
    {
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy([]);
        $eventId = $event->getId();

        $updateData = [
            "name" => "Événement modifié",
        ];

        $this->makeAuthenticatedRequest(
            "PUT",
            "/api/events/" . $eventId,
            $updateData,
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
        );

        $event = $this->entityManager
            ->getRepository(Event::class)
            ->find($eventId);

        $this->assertEquals("Événement modifié", $event->getName());
    }

    public function testAddUserToEvent(): void
    {
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy([]);
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(["email" => "user1@example.com"]);

        $this->makeAuthenticatedRequest(
            "GET",
            "/api/events/add/" . $user->getId() . "/" . $event->getId(),
            [],
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
        );

        // Vérifier que l'utilisateur a été ajouté à l'événement
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->find($event->getId());

        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($user->getId());

        $this->assertTrue($event->getUsers()->contains($user));
    }

    public function testSetSanta(): void
    {
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy([]);

        // Ajouter quelques utilisateurs à l'événement d'abord
        $user1 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(["email" => "user1@example.com"]);
        $user2 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(["email" => "user2@example.com"]);

        $event->addUser($user1);
        $event->addUser($user2);
        $this->entityManager->flush();

        $this->makeAuthenticatedRequest(
            "GET",
            "/api/events/set-santa/" . $event->getId(),
            [],
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
        );
    }

    public function testDeleteEvent(): void
    {
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy([]);
        $eventId = $event->getId();

        $this->makeAuthenticatedRequest(
            "DELETE",
            "/api/events/" . $eventId,
            [],
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $this->client->getResponse()->getStatusCode(),
        );

        // Vérifier que l'événement a été supprimé
        $deletedEvent = $this->entityManager
            ->getRepository(Event::class)
            ->find($eventId);
        $this->assertNull($deletedEvent);
    }

    public function testUnauthorizedAccess(): void
    {
        // Test sans token d'authentification
        $this->client->request("GET", "/api/events");

        $this->assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $this->client->getResponse()->getStatusCode(),
        );
    }

    public function testNotFoundEvent(): void
    {
        $this->makeAuthenticatedRequest(
            "GET",
            "/api/events/999999",
            [],
            $this->authToken,
        );

        $this->assertEquals(
            Response::HTTP_NOT_FOUND,
            $this->client->getResponse()->getStatusCode(),
        );
    }
}
