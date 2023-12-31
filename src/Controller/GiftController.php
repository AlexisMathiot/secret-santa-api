<?php

namespace App\Controller;

use App\Entity\GiftList;
use App\Repository\GiftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class GiftController extends AbstractController
{
    #[Route('api/gifts', name: 'gifts', methods: ['GET'])]
    public function getGifts(GiftRepository $giftRepository, SerializerInterface $serializer): JsonResponse
    {
        $giftList = $giftRepository->findAll();
        $jsonGiftList = $serializer->serialize($giftList, 'json', ['groups' => 'getGifts']);

        return new JsonResponse($jsonGiftList, Response::HTTP_OK, [], true);
    }

    #[Route('api/giftslist/{id}/gifts', name: 'giftList', methods: ['GET'])]
    public function getGiftsList(GiftList $giftList, SerializerInterface $serializer): JsonResponse
    {
        $jsonGiftList = $serializer->serialize($giftList, 'json', ['groups' => 'getGifts']);

        return new JsonResponse($jsonGiftList, Response::HTTP_OK, [], true);
    }

}
