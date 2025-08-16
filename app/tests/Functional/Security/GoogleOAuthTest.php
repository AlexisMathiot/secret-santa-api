<?php

// tests/Functional/Security/GoogleOAuthTest.php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GoogleOAuthTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );
        $this->userRepository = static::getContainer()->get(
            UserRepository::class,
        );

        // Nettoyer la base de données
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function testOAuthConnectRedirectsToGoogle(): void
    {
        $this->client->request("GET", "/oauth/connect/google");

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $location = $this->client->getResponse()->headers->get("location");
        $this->assertStringContainsString("accounts.google.com", $location);
        $this->assertStringContainsString("oauth2/v2/auth", $location);
    }

    public function testOAuthConnectWithInvalidServiceReturns404(): void
    {
        $this->client->request("GET", "/oauth/connect/invalid_service");

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSuccessfulGoogleAuthenticationWithExistingUser(): void
    {
        // Créer un utilisateur existant
        $existingUser = $this->createTestUser(
            "existing@example.com",
            "google123",
        );

        // Mocker les services OAuth
        $this->mockGoogleOAuthServices(
            $existingUser,
            "existing@example.com",
            "google123",
            true,
        );

        $this->client->request("GET", "/oauth/check/google?code=test_code");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
        );
        $this->assertArrayHasKey("token", $responseData);
        $this->assertNotEmpty($responseData["token"]);
    }

    public function testSuccessfulGoogleAuthenticationWithNewUser(): void
    {
        // S'assurer qu'aucun utilisateur n'existe avec cet email
        $existingUser = $this->userRepository->findOneBy([
            "email" => "newuser@example.com",
        ]);
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        // Mocker les services OAuth pour un nouvel utilisateur
        $this->mockGoogleOAuthServices(
            null,
            "newuser@example.com",
            "google456",
            true,
        );

        $this->client->request("GET", "/oauth/check/google?code=test_code");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que l'utilisateur a été créé
        $newUser = $this->userRepository->findOneBy([
            "email" => "newuser@example.com",
        ]);
        $this->assertNotNull($newUser);
        $this->assertEquals("google456", $newUser->getGoogleId());
        $this->assertContains("ROLE_USER", $newUser->getRoles());

        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
        );
        $this->assertArrayHasKey("token", $responseData);
    }

    public function testGoogleAuthenticationFailsWithUnverifiedEmail(): void
    {
        // Mocker les services OAuth avec un email non vérifié
        $this->mockGoogleOAuthServices(
            null,
            "unverified@example.com",
            "google789",
            false,
        );

        $this->client->request("GET", "/oauth/check/google?code=test_code");

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
        );
        $this->assertArrayHasKey("error", $responseData);
        $this->assertStringContainsString(
            "email not verify",
            $responseData["error"],
        );
    }

    private function createTestUser(string $email, string $googleId): User
    {
        $user = new User();
        $user
            ->setEmail($email)
            ->setUsername("Test User")
            ->setPassword("")
            ->setGoogleId($googleId)
            ->setRoles(["ROLE_USER"]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function mockGoogleOAuthServices(
        ?User $existingUser,
        string $email,
        string $googleId,
        bool $emailVerified,
    ): void {
        // Mock GoogleUser
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser->method("getEmail")->willReturn($email);
        $googleUser->method("getId")->willReturn($googleId);
        $googleUser->method("getFirstName")->willReturn("Test");
        $googleUser
            ->method("toArray")
            ->willReturn(["email_verified" => $emailVerified]);

        // Mock AccessToken
        $accessToken = $this->createMock(AccessToken::class);

        // Mock OAuth2ClientInterface
        $oauthClient = $this->createMock(OAuth2ClientInterface::class);
        $oauthClient->method("getAccessToken")->willReturn($accessToken);
        $oauthClient
            ->method("fetchUserFromToken")
            ->with($accessToken)
            ->willReturn($googleUser);

        // Mock ClientRegistry
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->method("getClient")
            ->with("google")
            ->willReturn($oauthClient);

        // Remplacer le service dans le container
        static::getContainer()->set(ClientRegistry::class, $clientRegistry);
    }
}
