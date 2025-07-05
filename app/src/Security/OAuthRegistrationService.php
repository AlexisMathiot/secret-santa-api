<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final readonly class OAuthRegistrationService
{

    public function __construct(private UserRepository $userRepository) {}

    /**
     * @param GoogleUser $resourceOwner
     */
    public function persist(ResourceOwnerInterface $resourceOwner): User
    {
        $user = (new User())
                ->setEmail($resourceOwner->getEmail())
                ->setUsername($resourceOwner->getFirstName())
                ->setPassword('')
                ->setGoogleId($resourceOwner->getId())
                ->setRoles(["ROLE_USER"]);

        $this->userRepository->add($user, true);
        return $user;
    }
}
