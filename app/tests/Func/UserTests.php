<?php

namespace App\Tests\Func;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Repository\UserRepository;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserTests extends ApiTestCase
{

    private array $users;

    public function __construct(private readonly UserRepository $usersRepository,
                                ?string                         $name = null,
                                array                           $data = [],
                                                                $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public static function userLoggedIn(): string
    {
        $response = static::createClient()->request('POST', '/api/login_check', ['json' => [
            'username' => 'Tata',
            'password' => 'password',
        ]]);

        return $response->toArray()['token'];
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetUsers(): void
    {
        // Test GET /api/users without auth
        $response = static::createClient()->request('GET',
            '/api/user',
            ['headers' => ['Accept' => 'application/json']]
        );
        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['code' => 401, 'message' => 'JWT Token not found']);

        // Test GET /api/users with auth
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
        // Test POST /api/users without auth
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

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function deleteUser(): void
    {
        // Load users
        $this->users = $this->usersRepository->findAll();
        $last_user = array_pop($this->users);

        // Test DELETE /api/users/{id} without auth
        $response = static::createClient()->request('DELETE', '/api/users/' . $last_user['id'], ['headers' => ['Accept' => 'application/json']]);
        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['code' => 401, 'message' => 'JWT Token not found']);

        // Test DELETE /api/users/{id} with auth
        $response = static::createClient()->request('DELETE', '/api/users/' . $last_user['id'], ['headers' => ['Accept' => 'application/json'], 'auth_bearer' => $this->jwtToken]);
        $this->assertResponseStatusCodeSame(204);
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

}
