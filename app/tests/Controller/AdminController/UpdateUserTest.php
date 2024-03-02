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

class UpdateUserTest extends AbstractApiTestCase
{
    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     * @throws NotSupported
     */
    public function testUpdateUser(): void
    {
        // Load user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => 1]);


        // Test PUT /api/admin/user/{id} without auth
        $response = static::createClient()->request('PUT', '/api/admin/user/' . $user->getId(), ['headers' => ['Accept' => 'application/json'], 'json' => [
            'email' => 'new-email@example.com',
            'username' => 'newname',
            'password' => 'newpassword',
            'roles' => ['ROLE_USER'],
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['code' => 401, 'message' => 'JWT Token not found']);

        // Test PUT /api/admin/user/{id} with auth
        $jwtToken = self::adminLoggedIn();

        $response = static::createClient()->request('PUT', '/api/admin/user/' . $user->getId(),
            ['headers' => ['Accept' => 'application/json'],
                'auth_bearer' => $jwtToken, 'json' => [
                'email' => 'new-email@example.com',
                'username' => 'new-name',
                'password' => 'new-password',
                'roles' => ['ROLE_USER'],
            ]]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);
    }
}