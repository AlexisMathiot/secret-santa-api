<?php

namespace App\Controller;

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

#[OA\Tag(name: 'Administration des utilisateurs', description: 'Gestion administrative des utilisateurs')]
class AdminController extends AbstractController
{
    #[Route('/api/admin/users', name: "user_list", methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/users',
        summary: 'Liste tous les utilisateurs',
        description: 'Récupère la liste complète de tous les utilisateurs du système',
        tags: ['Administration des utilisateurs']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des utilisateurs récupérée avec succès',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userList']))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié (JWT manquant ou invalide)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'JWT Token not found')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès interdit (droits administrateur requis)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function userList(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userlist = $userRepository->findAll();
        $jsonUserlist = $serializer->serialize($userlist, 'json', ['groups' => 'userList']);
        return new JsonResponse($jsonUserlist, Response::HTTP_OK, [], true);
    }

    #[Route('/api/admin/user/{id}', name: "user_detail", methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/user/{id}',
        summary: 'Détails d\'un utilisateur',
        description: 'Récupère les informations détaillées d\'un utilisateur spécifique',
        tags: ['Administration des utilisateurs']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique de l\'utilisateur',
        schema: new OA\Schema(type: 'integer', example: 42)
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails de l\'utilisateur récupérés avec succès',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['userDetail']))
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié (JWT manquant ou invalide)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'JWT Token not found')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès interdit (droits administrateur requis)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'User not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function userDetail(
        SerializerInterface $serializer,
        User                $user,
        UserData            $userData
    ): JsonResponse {
        $userArray = $userData->userDataToArray($user);
        $jsonUser = $serializer->serialize($userArray, 'json', ['groups' => 'userDetail']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/admin/user/{id}', name: "user_delete", methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/user/{id}',
        summary: 'Supprime un utilisateur',
        description: 'Supprime définitivement un utilisateur du système',
        tags: ['Administration des utilisateurs']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique de l\'utilisateur à supprimer',
        schema: new OA\Schema(type: 'integer', example: 123)
    )]
    #[OA\Response(
        response: 204,
        description: 'Utilisateur supprimé avec succès'
    )]
    #[OA\Response(
        response: 400,
        description: 'Impossible de supprimer l\'utilisateur',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous êtes organisateur d\'un événement')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié (JWT manquant ou invalide)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'JWT Token not found')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès interdit (droits administrateur requis)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'User not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function deleteUser(User $user, EntityManagerInterface $em,): JsonResponse
    {
        $eventOrganize = $user->getEventsOrganize();

        if ($eventOrganize->count() > 0) {
            return new JsonResponse(['error' => 'Vous êtes organisateur d\'un événement, merci de changer l\'organisateur'], Response::HTTP_BAD_REQUEST);
        }
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/user/{id}', name: "user_update", methods: ['PUT'])]
    #[OA\Put(
        path: '/api/admin/user/{id}',
        summary: 'Met à jour un utilisateur',
        description: 'Met à jour les informations d\'un utilisateur existant',
        tags: ['Administration des utilisateurs']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique de l\'utilisateur à mettre à jour',
        schema: new OA\Schema(type: 'integer', example: 42)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'Données de l\'utilisateur à mettre à jour (tous les champs sont optionnels)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nouveau.email@example.com'),
                new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'nouveauMotDePasse123!'),
                new OA\Property(property: 'firstname', type: 'string', example: 'Jean'),
                new OA\Property(property: 'lastname', type: 'string', example: 'Dupont'),
            ]
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Utilisateur mis à jour avec succès'
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation des données',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'propertyPath', type: 'string', example: 'email'),
                    new OA\Property(property: 'message', type: 'string', example: 'Cette valeur n\'est pas un email valide.')
                ]
            )
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié (JWT manquant ou invalide)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'JWT Token not found')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès interdit (droits administrateur requis)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'User not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function updateUser(
        Request                     $request,
        SerializerInterface         $serializer,
        User                        $currentUser,
        EntityManagerInterface      $em,
        ValidatorInterface          $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $updateUser = $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]
        );

        $errors = $validator->validate($updateUser);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $sendPassword = $serializer->deserialize($request->getContent(), User::class, 'json')->getPassword();
        if ($sendPassword !== null) {
            $updateUser->setPassword($passwordHasher->hashPassword($updateUser, $sendPassword));
        }

        $em->persist($updateUser);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
