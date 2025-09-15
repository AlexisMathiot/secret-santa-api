<?php

namespace App\Controller;

use App\Dto\AdminUpdateUserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

#[
    OA\Tag(
        name: "Administration des utilisateurs",
        description: "Gestion administrative des utilisateurs",
    ),
]
class AdminController extends AbstractController
{
    #[Route("/api/admin/users", name: "user_list", methods: ["GET"])]
    #[
        OA\Get(
            path: "/api/admin/users",
            summary: "Liste tous les utilisateurs",
            description: "Récupère la liste complète de tous les utilisateurs du système",
            tags: ["Administration des utilisateurs"],
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Liste des utilisateurs récupérée avec succès",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    ref: new Model(type: User::class, groups: ["userList"]),
                ),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié (JWT manquant ou invalide)",
            content: new OA\JsonContent(
                type: "object",
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
            description: "Accès interdit (droits administrateur requis)",
            content: new OA\JsonContent(
                type: "object",
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
    #[Security(name: "Bearer")]
    public function userList(
        UserRepository $userRepository,
        SerializerInterface $serializer,
    ): JsonResponse {
        $userlist = $userRepository->findAll();
        $jsonUserlist = $serializer->serialize($userlist, "json", [
            "groups" => "userList",
        ]);
        return new JsonResponse($jsonUserlist, Response::HTTP_OK, [], true);
    }

    #[Route("/api/admin/user/{id}", name: "user_detail", methods: ["GET"])]
    #[
        OA\Get(
            path: "/api/admin/user/{id}",
            summary: 'Détails d\'un utilisateur',
            description: 'Récupère les informations détaillées d\'un utilisateur spécifique',
            tags: ["Administration des utilisateurs"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: 'Identifiant unique de l\'utilisateur',
            schema: new OA\Schema(type: "integer", example: 42),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: 'Détails de l\'utilisateur récupérés avec succès',
            content: new OA\JsonContent(
                ref: new Model(type: User::class, groups: ["userDetail"]),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié (JWT manquant ou invalide)",
            content: new OA\JsonContent(
                type: "object",
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
            description: "Accès interdit (droits administrateur requis)",
            content: new OA\JsonContent(
                type: "object",
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
                type: "object",
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
    #[Security(name: "Bearer")]
    public function userDetail(
        SerializerInterface $serializer,
        User $user,
        UserData $userData,
    ): JsonResponse {
        $userArray = $userData->userDataToArray($user);
        return new JsonResponse($userArray, Response::HTTP_OK, [], true);
    }

    #[Route("/api/admin/user/{id}", name: "user_delete", methods: ["DELETE"])]
    #[
        OA\Delete(
            path: "/api/admin/user/{id}",
            summary: "Supprime un utilisateur",
            description: "Supprime définitivement un utilisateur du système",
            tags: ["Administration des utilisateurs"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: 'Identifiant unique de l\'utilisateur à supprimer',
            schema: new OA\Schema(type: "integer", example: 123),
        ),
    ]
    #[
        OA\Response(
            response: 204,
            description: "Utilisateur supprimé avec succès",
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: 'Impossible de supprimer l\'utilisateur',
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: 'Vous êtes organisateur d\'un événement',
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié (JWT manquant ou invalide)",
            content: new OA\JsonContent(
                type: "object",
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
            description: "Accès interdit (droits administrateur requis)",
            content: new OA\JsonContent(
                type: "object",
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
                type: "object",
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
    #[Security(name: "Bearer")]
    public function deleteUser(
        User $user,
        EntityManagerInterface $em,
    ): JsonResponse {
        $eventOrganize = $user->getEventsOrganize();

        if ($eventOrganize->count() > 0) {
            return new JsonResponse(
                [
                    "error" =>
                        'Vous êtes organisateur d\'un événement, merci de changer l\'organisateur',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[
        Route(
            "/api/admin/user/{id}",
            name: "user_update",
            requirements: ["id" => "\d+"],
            methods: ["PUT"],
        ),
    ]
    #[
        OA\Put(
            path: "/api/admin/user/{id}",
            summary: "Met à jour un utilisateur",
            description: 'Met à jour les informations d\'un utilisateur existant (accès administrateur requis)',
            tags: ["Administration des utilisateurs"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: 'Identifiant unique de l\'utilisateur à mettre à jour',
            schema: new OA\Schema(type: "integer", example: 42),
        ),
    ]
    #[
        OA\RequestBody(
            required: true,
            description: 'Données de l\'utilisateur à mettre à jour (tous les champs sont optionnels)',
            content: new OA\JsonContent(
                ref: new Model(type: AdminUpdateUserDto::class),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 204,
            description: "Utilisateur mis à jour avec succès",
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Erreur de validation des données",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    type: "object",
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
            response: 401,
            description: "Non authentifié (JWT manquant ou invalide)",
            content: new OA\JsonContent(
                type: "object",
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
            description: "Accès interdit (droits administrateur requis)",
            content: new OA\JsonContent(
                type: "object",
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
                type: "object",
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
            response: 409,
            description: "Conflit - Email ou pseudo déjà utilisé",
            content: new OA\JsonContent(
                type: "object",
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
            description: "Aucune donnée à mettre à jour",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Aucune donnée fournie pour la mise à jour",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 500,
            description: "Erreur serveur interne",
            content: new OA\JsonContent(
                type: "object",
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
    #[Security(name: "Bearer")]
    public function updateUser(
        User $user,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        try {
            // Désérialisation du DTO
            /** @var AdminUpdateUserDto $dto */
            $dto = $serializer->deserialize(
                $request->getContent(),
                AdminUpdateUserDto::class,
                "json",
            );

            // Vérifier qu'au moins un champ est fourni
            if (!$dto->hasAnyField()) {
                return new JsonResponse(
                    ["error" => "Aucune donnée fournie pour la mise à jour"],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

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

            // Vérifications d'unicité avant mise à jour
            if (
                $dto->getEmail() !== null &&
                $dto->getEmail() !== $user->getEmail()
            ) {
                $existingEmailUser = $em
                    ->getRepository(User::class)
                    ->findOneBy(["email" => $dto->getEmail()]);
                if ($existingEmailUser) {
                    return new JsonResponse(
                        [
                            "error" =>
                                "Un utilisateur avec cet email existe déjà",
                        ],
                        Response::HTTP_CONFLICT,
                    );
                }
            }

            if (
                $dto->getPseudo() !== null &&
                $dto->getPseudo() !== $user->getPseudo()
            ) {
                $existingPseudoUser = $em
                    ->getRepository(User::class)
                    ->findOneBy(["pseudo" => $dto->getPseudo()]);
                if ($existingPseudoUser) {
                    return new JsonResponse(
                        [
                            "error" =>
                                "Un utilisateur avec ce pseudo existe déjà",
                        ],
                        Response::HTTP_CONFLICT,
                    );
                }
            }

            // Application des modifications via le DTO
            $dto->applyToUser($user);

            // Traitement spécial pour le mot de passe (hashage)
            if ($dto->getPassword() !== null) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $dto->getPassword(),
                );
                $user->setPassword($hashedPassword);
            }

            // Mise à jour de la date de modification
            $user->setUpdatedAt();

            // Sauvegarde
            $em->persist($user);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Symfony\Component\Serializer\Exception\NotEncodableValueException $e) {
            return new JsonResponse(
                ["error" => "Format JSON invalide"],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ["error" => "Une erreur est survenue lors de la mise à jour"],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
