<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class GiftController extends AbstractController
{

    #[Route('/api/gift/{id}', name: 'deleteGift', methods: ['DELETE'])]
    public function deleteGift(Gift $gift, EntityManagerInterface $em): JsonResponse
    {

        $em->remove($gift);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/gift', name: "addGift", methods: ['POST'])]
    public function createBook(Request                $request,
                               SerializerInterface    $serializer,
                               EntityManagerInterface $em,
                               UrlGeneratorInterface  $urlGenerator): JsonResponse
    {

        $gift = $serializer->deserialize($request->getContent(), Gift::class, 'json');
        $em->persist($gift);

        /** @var User $user */
        $user = $this->getUser();

        $giftlist = $user->getGiftList();

        $em->flush();

        $jsonGiftlist = $serializer->serialize($gift, 'json', ['groups' => 'getGift']);

        $location = $urlGenerator->generate('detailBook', ['id' => $giftlist->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonGiftlist, Response::HTTP_CREATED, ["Location" => $location], true);
    }

}
