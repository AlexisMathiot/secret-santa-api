<?php

namespace App\Tests\Controller\EventController;

use App\Tests\AbstractApiTestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CreateEventTest extends AbstractApiTestCase
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testCreateEvent()
    {
        // test POST /api/events
        $jwtToken = self::userLoggedIn();

        $response = static::createClient()->request('POST', '/api/events',
            ['headers' => ['Accept' => 'application/json'], 'auth_bearer' => $jwtToken,
                'json' => [
                    'name' => 'new event'
                ]
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['name' => 'new event']);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testCreateEventWithoutName()
    {
        // test POST /api/events
        $jwtToken = self::userLoggedIn();

        $response = static::createClient()->request('POST', '/api/events',
            ['headers' => ['Accept' => 'application/json'], 'auth_bearer' => $jwtToken,
                'json' => [
                    'name' => ''
                ]
            ]
        );
        $this->assertResponseStatusCodeSame(400);
    }
}
