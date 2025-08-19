<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Entity\GiftList;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Gestion des cadeaux', description: 'Gestion des cadeaux et listes de cadeaux des utilisateurs')]
class GiftController extends AbstractController
{
    /**
     * @throws ORMException
     */
    #[Route('/api/gifts/{id}', name: 'gift_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/gifts/{id}',
        summary: 'Supprime un cadeau',
        description: 'Supprime définitivement un cadeau de la liste',
        tags: ['Gestion des cadeaux']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique du cadeau à supprimer',
        schema: new OA\Schema(type: 'integer', example: 123)
    )]
    #[OA\Response(
        response: 204,
        description: 'Cadeau supprimé avec succès'
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
        description: 'Accès interdit (vous n\'êtes pas propriétaire de ce cadeau)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Cadeau non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Gift not found')
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur interne du serveur',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Database error occurred')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function deleteGift(Gift $gift, EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->remove($gift);
            $em->flush();
        } catch (ORMException $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/gifts/{id}', name: "gift_detail", methods: 'GET')]
    #[OA\Get(
        path: '/api/gifts/{id}',
        summary: 'Détails d\'un cadeau',
        description: 'Récupère les informations détaillées d\'un cadeau spécifique',
        tags: ['Gestion des cadeaux']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique du cadeau',
        schema: new OA\Schema(type: 'integer', example: 42)
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails du cadeau récupérés avec succès',
        content: new OA\JsonContent(ref: new Model(type: Gift::class, groups: ['getGift']))
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
        description: 'Accès interdit (vous n\'êtes pas propriétaire de ce cadeau)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Cadeau non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Gift not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function getGiftDetail(Gift $gift, SerializerInterface $serializer): JsonResponse
    {
        $jsonGift = $serializer->serialize($gift, 'json', ['groups' => 'getGift']);
        return new JsonResponse($jsonGift, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gifts', name: "gift_list", methods: ['GET'])]
    #[OA\Get(
        path: '/api/gifts',
        summary: 'Liste des cadeaux de l\'utilisateur',
        description: 'Récupère la liste de tous les cadeaux de l\'utilisateur connecté',
        tags: ['Gestion des cadeaux']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des cadeaux récupérée avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id_user', type: 'integer', example: 123, description: 'Identifiant de l\'utilisateur'),
                new OA\Property(
                    property: 'gift_list',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Gift::class, groups: ['getGift'])),
                    description: 'Liste des cadeaux'
                )
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
    #[Security(name: 'Bearer')]
    public function getGift(SerializerInterface $serializer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $giftlist = $user->getGiftList();

        $jsonGiftlist = $serializer->serialize(["id_user" => $user->getId(), "gift_list" => $giftlist], 'json', ['groups' => 'getGift']);

        return new JsonResponse($jsonGiftlist, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gifts/{id}', name: "gift_create", methods: ['POST'])]
    #[OA\Post(
        path: '/api/gifts/{id}',
        summary: 'Ajoute un cadeau à une liste',
        description: 'Crée un nouveau cadeau et l\'ajoute à la liste de cadeaux spécifiée',
        tags: ['Gestion des cadeaux']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant de la liste de cadeaux',
        schema: new OA\Schema(type: 'integer', example: 42)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'Données du cadeau à créer',
        content: new OA\JsonContent(
            type: 'object',
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Livre de cuisine', description: 'Nom du cadeau'),
                new OA\Property(property: 'description', type: 'string', example: 'Un livre de recettes françaises', description: 'Description du cadeau'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 29.99, description: 'Prix du cadeau'),
                new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com/livre', description: 'URL vers le produit')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Cadeau créé avec succès',
        content: new OA\JsonContent(ref: new Model(type: Gift::class, groups: ['getGift'])),
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'URL vers le cadeau créé',
                schema: new OA\Schema(type: 'string', example: 'https://api.example.com/api/gifts/123')
            )
        ]
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation des données',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'propertyPath', type: 'string', example: 'name'),
                    new OA\Property(property: 'message', type: 'string', example: 'Cette valeur ne devrait pas être vide.')
                ]
            ),
            example: [
                [
                    'propertyPath' => 'name',
                    'message' => 'Cette valeur ne devrait pas être vide.'
                ]
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
        description: 'Accès interdit (vous n\'êtes pas propriétaire de cette liste)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Liste de cadeaux non trouvée',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Gift list not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function createGift(
        Request                $request,
        GiftList               $giftList,
        SerializerInterface    $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface  $urlGenerator,
        ValidatorInterface     $validator
    ): JsonResponse {
        $gift = $serializer->deserialize($request->getContent(), Gift::class, 'json');
        $errors = $validator->validate($gift);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($gift);

        /** @var User $user */
        $user = $this->getUser();
        $giftList->addGift($gift);
        $em->flush();

        $jsonGiftlist = $serializer->serialize($gift, 'json', ['groups' => 'getGift']);

        $location = $urlGenerator->generate(
            'gift_detail',
            ['id' => $gift->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($jsonGiftlist, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/gifts/{id}', name: "gift_update", methods: ['PUT'])]
    #[OA\Put(
        path: '/api/gifts/{id}',
        summary: 'Met à jour un cadeau',
        description: 'Met à jour les informations d\'un cadeau existant',
        tags: ['Gestion des cadeaux']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identifiant unique du cadeau à mettre à jour',
        schema: new OA\Schema(type: 'integer', example: 42)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'Nouvelles données du cadeau',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Livre de cuisine (édition spéciale)', description: 'Nouveau nom du cadeau'),
                new OA\Property(property: 'description', type: 'string', example: 'Un livre de recettes françaises avec photos', description: 'Nouvelle description'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 34.99, description: 'Nouveau prix'),
                new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com/livre-special', description: 'Nouvelle URL')
            ]
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Cadeau mis à jour avec succès'
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation des données',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'propertyPath', type: 'string', example: 'name'),
                    new OA\Property(property: 'message', type: 'string', example: 'Cette valeur ne devrait pas être vide.')
                ]
            ),
            example: [
                [
                    'propertyPath' => 'name',
                    'message' => 'Cette valeur ne devrait pas être vide.'
                ]
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
        description: 'Accès interdit (vous n\'êtes pas propriétaire de ce cadeau)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access Denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Cadeau non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Gift not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function updateGift(
        Gift                   $gift,
        SerializerInterface    $serializer,
        Request                $request,
        ValidatorInterface     $validator,
        EntityManagerInterface $em
    ): JsonResponse {
        $newGift = $serializer->deserialize($request->getContent(), Gift::class, 'json');
        $gift->setName($newGift->getName());

        $errors = $validator->validate($gift);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($gift);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
