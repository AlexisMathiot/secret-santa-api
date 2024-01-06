<?php

namespace App\Controller;

use App\Entity\GiftList;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class AdminController extends AbstractController
{

    #[Route('/api/admin/users', name: "userList", methods: ['GET'])]
    public function userList(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userlist = $userRepository->findAll();

        $jsonUserlistJson = $serializer->serialize($userlist, 'json', ['groups' => 'userList']);

        return new JsonResponse($jsonUserlistJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/admin/user', name: "createUser", methods: ['POST'])]
    public function createUser(Request                     $request,
                               SerializerInterface         $serializer,
                               UserPasswordHasherInterface $passwordHasher,
                               EntityManagerInterface      $em,
                               UrlGeneratorInterface       $urlGenerator): JsonResponse
    {

        /** @var User $user */
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setRoles(["ROLE_USER"]);

        $giftlist = new GiftList();

        $user->addGiftList($giftlist);

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json');

        $location = $urlGenerator->generate('userList', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/admin/user/{username}', name: "deleteUser", methods: ['DELETE'])]
    public function deleteUser(UserRepository         $userRepository,
                               string                 $username,
                               EntityManagerInterface $em): JsonResponse
    {

        $user = $userRepository->findOneBy(['username' => $username]);

        if ($user !== null) {
            $em->remove($user);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $message = "User with username {$username} not found";
        return new JsonResponse($message, Response::HTTP_NOT_FOUND);

    }

    #[Route('/api/admin/user/{username}', name: "updateUser", methods: ['PUT'])]
    public function updateUser(Request                $request,
                               SerializerInterface    $serializer,
                               string                 $username,
                               EntityManagerInterface $em,
                               UserRepository         $userRepository): JsonResponse
    {
        $currentUser = $userRepository->findOneBy(['username' => $username]);

        if ($currentUser !== null) {
            $updateUser = $serializer->deserialize($request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]
            );

            $em->persist($updateUser);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $message = "User with username {$username} not found";
        return new JsonResponse($message, Response::HTTP_NOT_FOUND);
    }



}
