<?php

namespace App\Tests\Controller\AdminController;

use App\Tests\AbstractApiTestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserTest extends AbstractApiTestCase
{
    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetUsers(): void
    {
        // Test GET /api/user without auth
        $response = static::createClient()->request('GET',
            '/api/user',
            ['headers' => ['Accept' => 'application/json']]
        );
        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['code' => 401, 'message' => 'JWT Token not found']);

        // Test GET /api/user with auth
        $jwtToken = self::userLoggedIn();

        $response = static::createClient()->request('GET', '/api/user',
            ['headers' => ['Accept' => 'application/json'],
                'auth_bearer' => $jwtToken]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testCreateUser(): void
    {
        // Test POST /api/signin
        $response = static::createClient()->request('POST',
            '/api/signin',
            ['headers' => ['Accept' => 'application/json'],
                'json' => [
                    'email' => 'test-new-user@example.com',
                    'username' => 'Toto',
                    'password' => 'password',
                    'roles' => ['ROLE_USER'],
                ]]
        );
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }
}
