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

class GiftController extends AbstractController
{

    /**
     * @throws ORMException
     */
    #[Route('/api/gifts/{id}', name: 'gift_delete', methods: ['DELETE'])]
    public function deleteGift(Gift $gift, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $userGiftList = $user->getGiftList();

        if (!$userGiftList->getGifts()->contains($gift)) {
            return new JsonResponse('Vous n\'avez pas les droits pour supprimer ce cadeau', Response::HTTP_FORBIDDEN);
        }

        try {
            $em->remove($gift);
            $em->flush();
        } catch (ORMException $e) {
            throw new ORMException('erreur : ' . $e);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/gifts/{id}', name: "gift_detail", methods: 'GET')]
    public function getGiftDetail(Gift $gift, SerializerInterface $serializer): JsonResponse
    {
        $jsonGift = $serializer->serialize($gift, 'json', ['groups' => 'getGift']);
        return new JsonResponse($jsonGift, Response::HTTP_OK, [], true);

    }

    #[Route('/api/gifts', name: "gift_list", methods: ['GET'])]
    public function getGift(SerializerInterface $serializer): JsonResponse
    {

        /** @var User $user */
        $user = $this->getUser();
        $giftlist = $user->getGiftList();

        $jsonGiftlist = $serializer->serialize(["id_user" => $user->getId(), "gift_list" => $giftlist], 'json', ['groups' => 'getGift']);

        return new JsonResponse($jsonGiftlist, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gifts/{id}', name: "gift_create", methods: ['POST'])]
    public function createGift(Request                $request,
                               GiftList               $giftList,
                               SerializerInterface    $serializer,
                               EntityManagerInterface $em,
                               UrlGeneratorInterface  $urlGenerator,
                               ValidatorInterface     $validator): JsonResponse
    {

        $gift = $serializer->deserialize($request->getContent(), Gift::class, 'json');
        $errors = $validator->validate($gift);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST);
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
    public function updateGift(Gift                   $gift,
                               SerializerInterface    $serializer,
                               Request                $request,
                               ValidatorInterface     $validator,
                               EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $giftlist = $user->getGiftList();

        if (!$giftlist->getGifts()->contains($gift)) {
            return new JsonResponse('Vous n\'avez pas les droits pour modifier ce cadeau', Response::HTTP_FORBIDDEN);
        }

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

    #[Route('/api/giftslist', name: "gift_list_create", methods: ['POST'])]
    public function createGiftList(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $giftlist = new GiftList();
        $em->persist($giftlist);

        $user->addGiftList($giftlist);

        $em->flush();

        return new JsonResponse('Liste de cadeaux crée', Response::HTTP_OK, [], true);

    }
}
