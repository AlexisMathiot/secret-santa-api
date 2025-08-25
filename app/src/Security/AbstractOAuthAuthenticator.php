<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

abstract class AbstractOAuthAuthenticator extends OAuth2Authenticator
{
    protected string $serviceName = '';
    private string $frontendUrl;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly UserRepository $userRepository,
        private readonly OAuthRegistrationService $registrationService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        string $frontendUrl = 'http://localhost:3000'
    ) {
        $this->frontendUrl = $frontendUrl;
    }

    public function supports(Request $request): ?bool
    {
        return 'auth_oauth_check' === $request->attributes->get('_route') && $request->get('service') === $this->serviceName;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        // Génération du token JWT via LexikJWT
        $jwt = $this->jwtManager->create($user);

        // Créer un cookie HTTPOnly sécurisé
        $cookie = new Cookie(
            name: 'jwt_token',
            value: $jwt,
            expire: time() + (24 * 60 * 60), // 24 heures
            path: '/',
            domain: null,
            secure: false, // true en production avec HTTPS
            httpOnly: true, // Empêche l'accès via JavaScript côté client
            raw: false,
            sameSite: Cookie::SAMESITE_LAX
        );

        // Créer la réponse de redirection vers le frontend
        $redirectUrl = $this->frontendUrl . '/auth/callback';
        $response = new RedirectResponse($redirectUrl);
        $response->headers->setCookie($cookie);

        // Déclencher l'événement LexikJWT pour la cohérence
        $data = ['token' => $jwt];
        $event = new AuthenticationSuccessEvent($data, $user, $response);
        $this->eventDispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        // Redirection vers le frontend avec un paramètre d'erreur
        $redirectUrl = $this->frontendUrl . '/auth/error?message=' . urlencode($message);

        return new RedirectResponse($redirectUrl);
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->fetchAccessToken($this->getClient());
        $resourceOwner = $this->getRessourceOwnerFromCredentials($credentials);

        $user = $this->getUserFromRessourceOwner($resourceOwner, $this->userRepository);

        if (null === $user) {
            $user = $this->registrationService->persist($resourceOwner);
        }

        return new SelfValidatingPassport(
            userBadge: new UserBadge($user->getUserIdentifier(), fn() => $user),
            badges: [
                new RememberMeBadge()
            ]
        );
    }

    protected function getRessourceOwnerFromCredentials(AccessToken $credentials): ResourceOwnerInterface
    {
        return $this->getClient()->fetchUserFromToken($credentials);
    }

    private function getClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient($this->serviceName);
    }

    abstract protected function getUserFromRessourceOwner(ResourceOwnerInterface $resourceOwner, UserRepository $userRepository): ?User;
}
