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

            $url = $baseUrl . "/reset-password/" . $token;

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

    #[Route('/api/login_check', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
        // Cette méthode ne sera jamais exécutée car Symfony intercepte la requête
        // Elle sert juste à créer la route pour que Symfony puisse l'intercepter
        throw new \LogicException('This method can be blank - it will be intercepted by the login key on your firewall.');
    }
}
