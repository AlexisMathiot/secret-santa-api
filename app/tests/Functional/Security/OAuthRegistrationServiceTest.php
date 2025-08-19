<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\OAuthRegistrationService;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\TestCase;

class OAuthRegistrationServiceTest extends TestCase
{
    public function testPersistCreatesUserFromGoogleData(): void
    {
        // Mock du GoogleUser
        $googleUser = $this->createMock(GoogleUser::class);
        /** @var GoogleUser&\PHPUnit\Framework\MockObject\MockObject $googleUser */
        $googleUser->method("getEmail")->willReturn("test@example.com");
        $googleUser->method("getFirstName")->willReturn("John");
        $googleUser->method("getId")->willReturn("google123");

        // Mock du UserRepository
        $userRepository = $this->createMock(UserRepository::class);
        /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject $userRepository */
        $userRepository
            ->expects($this->once())
            ->method("add")
            ->with(
                $this->callback(
                    fn(User $user) => $user->getEmail() ===
                        "test@example.com" &&
                        $user->getUsername() === "John" &&
                        $user->getGoogleId() === "google123" &&
                        $user->getPassword() === "" &&
                        $user->getRoles() === ["ROLE_USER"],
                ),
                true,
            );

        $service = new OAuthRegistrationService($userRepository);
        $result = $service->persist($googleUser);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals("test@example.com", $result->getEmail());
        $this->assertEquals("John", $result->getUsername());
        $this->assertEquals("google123", $result->getGoogleId());
    }
}
