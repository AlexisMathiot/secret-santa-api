<?php

namespace App\Tests\Controller\AdminController;

use App\Entity\User;
use App\Tests\AbstractApiTestCase;
use Doctrine\ORM\Exception\NotSupported;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DeleteUserTest extends AbstractApiTestCase
{
    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws NotSupported
     */
    public function testDeleteUser(): void
    {
        // Load user
        $this->user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => 1]);

        // Test DELETE /api/admin/user/{id} without auth
        $response = static::createClient()->request(
            'DELETE',
            '/api/admin/user/' . $this->user->getId(),
            ['headers' => ['Accept' => 'application/json']]
        );
        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['code' => 401, 'message' => 'JWT Token not found']);

        // Test DELETE /api/admin/user/{id} with auth
        $jwtToken = self::adminLoggedIn();
        $response = static::createClient()->request(
            'DELETE',
            '/api/admin/user/' . $this->user->getId(),
            ['headers' => ['Accept' => 'application/json'], 'auth_bearer' => $jwtToken]
        );
        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws NotSupported
     */
    public function testDeleteUserOrganiser(): void
    {
        // Load user
        $this->user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => 2]);

        // Test DELETE /api/admin/user/{id} with auth
        $jwtToken = self::adminLoggedIn();
        $response = static::createClient()->request(
            'DELETE',
            '/api/admin/user/' . $this->user->getId(),
            ['headers' => ['Accept' => 'application/json'], 'auth_bearer' => $jwtToken]
        );
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertEquals("Vous êtes organisateur d'un évènement, merci de changer l'organisateur", $response->getContent());
    }
}
