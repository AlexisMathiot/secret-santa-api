<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserData;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'api_user_detail', methods: 'GET')]
    public function currentUserDetail(SerializerInterface $serializer, UserData $userData): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userArray = $userData->userDataToArray($user);
        $jsonUser = $serializer->serialize($userArray, 'json', ['groups' => 'userDetail']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);

    }

    #[Route('/api/user/delete-compte', name: 'api_user', methods: 'GET')]
    public function deleteUserCompte(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $entityManager->remove($user);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
