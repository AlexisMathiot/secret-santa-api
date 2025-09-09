<?php

namespace App\Controller;

use App\Entity\User;
use App\Dto\CreateUserDto;
use App\Dto\UserResponseDto;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class RegisterController extends AbstractController
{
    /**
     * Création d'un nouvel utilisateur
     */
    #[Route("/signin", name: "signin", methods: ["POST"])]
    #[
        OA\Post(
            path: "/api/signin",
            summary: 'Création d\'un nouvel utilisateur',
            description: "Créer un nouveau compte utilisateur dans le système",
            tags: ["Gestion des utilisateurs"],
        ),
    ]
    #[
        OA\RequestBody(
            description: 'Données de création de l\'utilisateur',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: CreateUserDto::class),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 201,
            description: "Utilisateur créé avec succès",
            content: new OA\JsonContent(
                ref: new Model(type: UserResponseDto::class),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Erreurs de validation",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(
                            property: "property",
                            type: "string",
                            example: "email",
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: 'L\'email doit être valide',
                        ),
                    ],
                ),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 409,
            description: "Utilisateur déjà existant",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Un utilisateur avec cet email existe déjà",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 422,
            description: "Données non traitables",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Invalid JSON format",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 500,
            description: "Erreur serveur",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: 'Une erreur est survenue lors de la création de l\'utilisateur',
                    ),
                ],
            ),
        ),
    ]
    #[Route("/api/signin", name: "api_signin", methods: ["POST"])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        try {
            // Désérialisation du DTO
            /** @var CreateUserDto $dto */
            $dto = $serializer->deserialize(
                $request->getContent(),
                CreateUserDto::class,
                "json",
            );

            // Validation du DTO
            $errors = $validator->validate($dto);
            if ($errors->count() > 0) {
                return new JsonResponse(
                    $serializer->serialize($errors, "json"),
                    Response::HTTP_BAD_REQUEST,
                    [],
                    true,
                );
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $em
                ->getRepository(User::class)
                ->findOneBy(["email" => $dto->getEmail()]);
            if ($existingUser) {
                return new JsonResponse(
                    ["error" => "Un utilisateur avec cet email existe déjà"],
                    Response::HTTP_CONFLICT,
                );
            }

            // Créer l'entité User
            $user = new User();
            $user->setEmail($dto->getEmail());
            $user->setPseudo($dto->getPseudo());
            $user->setUsername($dto->getUsername());
            $user->setPassword(
                $passwordHasher->hashPassword($user, $dto->getPassword()),
            );
            $user->setRoles(["ROLE_USER"]);
            $user->setCreatedAt();

            $em->persist($user);
            $em->flush();

            // Création du DTO de réponse (sans le mot de passe)
            $responseDto = UserResponseDto::fromUser($user);
            $jsonResponse = $serializer->serialize($responseDto, "json");

            return new JsonResponse(
                $jsonResponse,
                Response::HTTP_CREATED,
                [],
                true,
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    "error" =>
                        'Une erreur est survenue lors de la création de l\'utilisateur',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[
        Route(
            "/api/send-email-confirmation-inscription/{email}",
            name: "api_send_confirmation_inscription_email",
            methods: ["GET"],
        ),
    ]
    public function sendConfirmationInsriptionEmail(
        MailerService $mail,
        string $email,
        UserRepository $userRepository,
    ): JsonResponse {
        $user = $userRepository->findOneBy(["email" => $email]);
        if ($user !== null) {
            $baseurl = $this->getParameter("app.front_base_url");
            $url = $baseurl . "/login";
            $context = compact("user", "url");
            $mail->send(
                "no-reply@domain.fr",
                $user->getEmail(),
                "Bienvenue sur SecretSanta",
                "confirmation_inscription",
                $context,
            );
            return new JsonResponse(
                "Invitation envoyé",
                Response::HTTP_OK,
                [],
                true,
            );
        }

        return new JsonResponse(
            "Email non valide",
            Response::HTTP_NOT_FOUND,
            [],
            true,
        );
    }
}
