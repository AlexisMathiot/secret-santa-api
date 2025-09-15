<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\MailerService;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

class SecurityController extends AbstractController
{
    public const array SCOPES = [
        "google" => [],
    ];

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    #[Route(path: "/forgot-password", name: "forgot_password", methods: "POST")]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager,
        MailerService $mail,
    ): JsonResponse {
        $email = json_decode($request->getContent(), true)["email"];
        $user = $userRepository->findOneBy(["email" => $email]);

        if ($user !== null) {
            $token = $tokenGenerator->generateToken();
            $user->setResetToken($token);
            $user->setTimeSendResetPasswordLink(
                new \DateTime("now", new DateTimeZone("Europe/Paris")),
            );
            $entityManager->persist($user);
            $entityManager->flush();

            $baseUrl = $this->getParameter("app.front_base_url");

            $url = "{$baseUrl}/reset-password/{$token}";

            $context = compact("url", "user");

            $mail->send(
                "no-reply@domain.fr",
                $user->getEmail(),
                "Réinitialisation de mot de passe",
                "reset_password",
                $context,
            );

            return new JsonResponse(
                "Mail de changement de mot de passe envoyé",
                Response::HTTP_OK,
                [],
                true,
            );
        }

        return new JsonResponse(
            "Un problème est survenu",
            Response::HTTP_BAD_REQUEST,
            [],
            true,
        );
    }

    /**
     * @throws Exception
     */
    #[Route(path: "/reset-password", name: "reset_password", methods: "POST")]
    public function resetPassword(
        UserRepository $userRepository,
        Request $request,
        SerializerInterface $serializer,
    ): Response {
        $token = json_decode($request->getContent(), true)["token"];
        $user = $userRepository->findOneBy(["resetToken" => $token]);
        if ($user !== null) {
            $now = new \DateTime("now", new DateTimeZone("Europe/Paris"));
            $_date = new \DateTime(
                $user->getTimeSendResetPasswordLink()->format("Y-m-d H:i:s"),
                new DateTimeZone("Europe/Paris"),
            );
            $numberMinutesSinceMailSend = $now->diff($_date)->i;
            if ($numberMinutesSinceMailSend > 120) {
                return new JsonResponse(
                    "Durée de validité du lien dépassé merci de refaire une demande de modification de mot de passe",
                    Response::HTTP_OK,
                    [],
                    true,
                );
            }
            $jsonUser = $serializer->serialize($user, "json", [
                "groups" => "userResetPassword",
            ]);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(
            "Utilisateur non trouvé",
            Response::HTTP_NOT_FOUND,
            [],
            true,
        );
    }

    #[
        Route(
            path: "/reset-password-set",
            name: "reset_password_set",
            methods: "POST",
        ),
    ]
    public function setResetPassword(
        UserRepository $userRepository,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $token = json_decode($request->getContent(), true)["token"];
        $password = json_decode($request->getContent(), true)["password"];
        $user = $userRepository->findOneBy(["resetToken" => $token]);

        if ($user !== null) {
            $user->setResetToken("");
            $user->setTimeSendResetPasswordLink(null);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(
                "Mot de passe changé",
                Response::HTTP_OK,
                [],
                true,
            );
        }

        return new JsonResponse(
            "Utilisateur non trouvé",
            Response::HTTP_NOT_FOUND,
            [],
            true,
        );
    }

    #[
        Route(
            "/oauth/connect/{service}",
            name: "auth_oauth_connect",
            methods: ["GET"],
        ),
    ]
    public function connect(
        string $service,
        ClientRegistry $clientRegistry,
    ): RedirectResponse {
        if (!in_array($service, array_keys(self::SCOPES), true)) {
            throw $this->createNotFoundException();
        }

        return $clientRegistry
            ->getClient($service)
            ->redirect(self::SCOPES[$service], []);
    }

    #[
        Route(
            "/oauth/check/{service}",
            name: "auth_oauth_check",
            methods: ["GET", "POST"],
        ),
    ]
    public function check(): Response
    {
        return new Response(status: 200);
    }

    #[Route("/api/login_check", name: "api_login", methods: ["POST"])]
    #[
        OA\Post(
            path: "/api/login_check",
            operationId: "login",
            summary: "Authentification utilisateur",
            description: "Authentifie un utilisateur et retourne un token JWT",
            tags: ["Authentification"],
        ),
    ]
    #[
        OA\RequestBody(
            description: "Identifiants de connexion",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["email", "password"],
                properties: [
                    new OA\Property(
                        property: "email",
                        type: "string",
                        format: "email",
                        description: 'Adresse email de l\'utilisateur',
                        example: "user@example.com",
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        format: "password",
                        description: 'Mot de passe de l\'utilisateur',
                        example: "motdepasse123",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Authentification réussie",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "token",
                        type: "string",
                        description: 'Token JWT pour l\'authentification',
                        example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
                    ),
                    new OA\Property(
                        property: "refresh_token",
                        type: "string",
                        description: "Token de rafraîchissement (optionnel)",
                        example: "def502003c4f5c8b7e7c...",
                    ),
                    new OA\Property(
                        property: "user",
                        type: "object",
                        description: "Informations utilisateur (optionnel)",
                        properties: [
                            new OA\Property(
                                property: "id",
                                type: "integer",
                                example: 1,
                            ),
                            new OA\Property(
                                property: "email",
                                type: "string",
                                example: "user@example.com",
                            ),
                            new OA\Property(
                                property: "roles",
                                type: "array",
                                items: new OA\Items(type: "string"),
                                example: ["ROLE_USER"],
                            ),
                        ],
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Identifiants invalides",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "code",
                        type: "integer",
                        example: 401,
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Invalid credentials.",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Données manquantes ou invalides",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "code",
                        type: "integer",
                        example: 400,
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Bad Request",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 429,
            description: "Trop de tentatives de connexion",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "code",
                        type: "integer",
                        example: 429,
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Too many login attempts, please try again later.",
                    ),
                ],
            ),
        ),
    ]
    public function login(): void
    {
        // Cette méthode ne sera jamais exécutée car Symfony intercepte la requête
        // Elle sert juste à créer la route pour que Symfony puisse l'intercepter
        throw new \LogicException(
            "This method can be blank - it will be intercepted by the login key on your firewall.",
        );
    }
}
