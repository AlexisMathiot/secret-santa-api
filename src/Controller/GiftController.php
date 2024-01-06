<?php

namespace App\Controller;

use App\Entity\Gift;
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

class GiftController extends AbstractController
{

    /**
     * @throws ORMException
     */
    #[Route('/api/gift/{id}', name: 'deleteGift', methods: ['DELETE'])]
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

    #[Route('/api/gifts', name: "listGift", methods: ['GET'])]
    public function getGift(SerializerInterface $serializer): JsonResponse
    {

        /** @var User $user */
        $user = $this->getUser();
        $giftlist = $user->getGiftList();

        $jsonGiftlist = $serializer->serialize($giftlist, 'json', ['groups' => 'getGifts']);

        return new JsonResponse($jsonGiftlist, Response::HTTP_OK, [], true);
    }

    #[Route('/api/gift', name: "createGift", methods: ['POST'])]
    public function createGift(Request                $request,
                               SerializerInterface    $serializer,
                               EntityManagerInterface $em,
                               UrlGeneratorInterface  $urlGenerator): JsonResponse
    {

        $gift = $serializer->deserialize($request->getContent(), Gift::class, 'json');
        $em->persist($gift);

        /** @var User $user */
        $user = $this->getUser();

        $giftlist = $user->getGiftList();
        $giftlist->addGift($gift);
        $em->flush();

        $jsonGiftlist = $serializer->serialize($gift, 'json', ['groups' => 'getGift']);

        $location = $urlGenerator->generate(
            'listGift',
            ['id' => $giftlist->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($jsonGiftlist, Response::HTTP_CREATED, ["Location" => $location], true);
    }

}
