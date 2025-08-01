<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class CustomJsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/login_check' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email'])) {
            throw new BadCredentialsException('Email manquant');
        }

        $email = $data['email'];
        
        // Vérifier si c'est une connexion OAuth (pas de mot de passe)
        if (!isset($data['password']) && isset($data['oauth_user_id'])) {
            // Connexion OAuth - validation par ID utilisateur
            $user = $this->userRepository->find($data['oauth_user_id']);
            
            if (!$user || $user->getEmail() !== $email) {
                throw new BadCredentialsException('Utilisateur OAuth invalide');
            }
            
            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier(), fn() => $user)
            );
        }
        
        // Connexion classique avec mot de passe
        if (!isset($data['password'])) {
            throw new BadCredentialsException('Mot de passe manquant');
        }

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                return $this->userRepository->findOneBy(['email' => $userIdentifier]);
            }),
            new PasswordCredentials($data['password'])
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Laisse LexikJWT gérer le succès
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey()
        ], Response::HTTP_UNAUTHORIZED);
    }
}