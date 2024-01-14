<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: "home", methods: ['GET'])]
    public function userList(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        echo xdebug_info();
    }
}