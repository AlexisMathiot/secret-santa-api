<?php

namespace App\Controller;

use App\Entity\User;
use App\Dto\UpdateUserPseudoDto;
use App\Dto\UserResponseDto;
use App\Service\UserData;
use Symfony\Component\Routing\Requirement\Requirement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

class UserController extends AbstractController
{
    #[Route("/api/user", name: "api_user_detail", methods: "GET")]
    public function currentUserDetail(
        SerializerInterface $serializer,
        UserData $userData,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $userArray = $userData->userDataToArray($user);
        $jsonUser = $serializer->serialize($userArray, "json", [
            "groups" => "userDetail",
        ]);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route("/api/user/delete-compte", name: "api_user", methods: "GET")]
    public function deleteUserCompte(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var  User $user */
        $eventOrganize = $user->getEventsOrganize();

        if ($eventOrganize->count() > 0) {
            return new JsonResponse(
                'Vous êtes organisateur d\'un évènement, merci de changer l\'organisateur',
            );
        }

        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Mise à jour du pseudo utilisateur
     */
    #[Route("/api/user/{id}", name: "api_user_update_surname", methods: "PUT")]
    #[
        Route(
            "/user/{id}",
            name: "user_update_surname",
            requirements: ["id" => Requirement::DIGITS],
            methods: ["PUT"],
        ),
    ]
    #[
        OA\Put(
            path: "/api/user/{id}",
            summary: "Mise à jour du pseudo utilisateur",
            description: 'Met à jour le pseudo d\'un utilisateur existant',
            tags: ["Gestion des utilisateurs"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            description: 'Identifiant de l\'utilisateur',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 42),
        ),
    ]
    #[
        OA\RequestBody(
            description: "Données de mise à jour du pseudo",
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: UpdateUserPseudoDto::class),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Pseudo mis à jour avec succès",
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
                            example: "pseudo",
                        ),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Le pseudo doit contenir au moins 2 caractères",
                        ),
                    ],
                ),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: "Accès interdit - Vous ne pouvez pas modifier ce profil",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Utilisateur non trouvé",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "User not found",
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
                        example: "Une erreur est survenue lors de la mise à jour",
                    ),
                ],
            ),
        ),
    ]
    public function updateSurname(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        try {
            // Désérialisation du DTO
            /** @var UpdateUserPseudoDto $dto */
            $dto = $serializer->deserialize(
                $request->getContent(),
                UpdateUserPseudoDto::class,
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

            // Mise à jour de l'entité
            $user->setPseudo($dto->getPseudo());
            $user->setUpdatedAt();
            $em->persist($user);
            $em->flush();

            // Création du DTO de réponse
            $responseDto = UserResponseDto::fromUser($user);
            $jsonResponse = $serializer->serialize($responseDto, "json");

            return new JsonResponse($jsonResponse, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(
                ["error" => "Une erreur est survenue lors de la mise à jour"],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
