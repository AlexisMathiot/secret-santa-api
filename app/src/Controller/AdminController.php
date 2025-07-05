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

class AdminController extends AbstractController
{

    #[Route('/api/admin/users', name: "user_list", methods: ['GET'])]
    public function userList(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userlist = $userRepository->findAll();

        $jsonUserlist = $serializer->serialize($userlist, 'json', ['groups' => 'userList']);

        return new JsonResponse($jsonUserlist, Response::HTTP_OK, [], true);
    }

    #[Route('/api/admin/user/{id}', name: "user_detail", methods: ['GET'])]
    public function userDetail(SerializerInterface $serializer,
                               User                $user,
                               UserData            $userData): JsonResponse
    {
        $userArray = $userData->userDataToArray($user);
        $jsonUser = $serializer->serialize($userArray, 'json', ['groups' => 'userDetail']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/admin/user/{id}', name: "user_delete", methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em,): JsonResponse
    {
        $eventOrganize = $user->getEventsOrganize();

        if ($eventOrganize->count() > 0) {
            return new JsonResponse('Vous êtes organisateur d\'un évènement, merci de changer l\'organisateur');
        }
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/user/{id}', name: "user_update", methods: ['PUT'])]
    public function updateUser(Request                     $request,
                               SerializerInterface         $serializer,
                               User                        $currentUser,
                               EntityManagerInterface      $em,
                               ValidatorInterface          $validator,
                               UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $updateUser = $serializer->deserialize($request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]
        );

        $errors = $validator->validate($updateUser);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST, [], true);
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
