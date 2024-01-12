<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserData;
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
}
