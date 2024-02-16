<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Santa;
use App\Entity\User;
use App\Repository\SantaRepository;
use App\Repository\UserRepository;
use App\Service\UserData;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Collection;
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

    #[Route('/api/admin/user/{username}', name: "user_detail", methods: ['GET'])]
    public function userDetail(UserRepository      $userRepository,
                               SerializerInterface $serializer,
                               string              $username,
                               UserData            $userData
    ): JsonResponse
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        if ($user !== null) {
            $userArray = $userData->userDataToArray($user);
            $jsonUser = $serializer->serialize($userArray, 'json', ['groups' => 'userDetail']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }

        $message = "Utilisateur avec le username {$username} n'existe pas";
        return new JsonResponse($message, Response::HTTP_NOT_FOUND, [], true);

    }

    #[Route('/api/admin/user/{username}', name: "user_delete", methods: ['DELETE'])]
    public function deleteUser(UserRepository         $userRepository,
                               string                 $username,
                               EntityManagerInterface $em,
                               SantaRepository        $santaRepository): JsonResponse
    {

        $user = $userRepository->findOneBy(['username' => $username]);
        $santas = $santaRepository->findBy(['user' => $user]);
        $eventOrganize = $user->getEventsOrganize();

        if ($eventOrganize->count() > 0) {
            return new JsonResponse('Vous êtes organisateur d\'un évènement, merci de changer l\'organisateur');
        }

        if ($user !== null) {
            $em->flush();

            foreach ($santas as $santa) {
                $em->remove($santa);
            }
            $em->flush();
            $em->remove($user);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $message = "User with username {$username} not found";
        return new JsonResponse($message, Response::HTTP_NOT_FOUND);

    }

    #[
        Route('/api/admin/user/{username}', name: "user_update", methods: ['PUT'])]
    public function updateUser(Request                     $request,
                               SerializerInterface         $serializer,
                               string                      $username,
                               EntityManagerInterface      $em,
                               UserRepository              $userRepository,
                               ValidatorInterface          $validator,
                               UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $currentUser = $userRepository->findOneBy(['username' => $username]);

        if ($currentUser !== null) {
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

        $message = "User with username {$username} not found";
        return new JsonResponse($message, Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/admin/users/setsanta/{id}', name: "user_set_santa", methods: ['GET'])]
    public function setSanta(EntityManagerInterface $em, Event $event): JsonResponse
    {
        $users = $event->getUsers();

        if ($users->count() <= 1) {
            return new JsonResponse('Il doit y avoir au moins 2 personnes participant a l\'évènement');
        }

        $this->clearSantasForEvant($event, $em);

        foreach ($users as $i => $user) {
            $santa = new Santa();
            $santa->setEvent($event);
            $santa->setUser($user);
            $santa->setSanta($users[($i + 1) % count($users)]);
            $em->persist($santa);
        }

        $em->flush();
        $message = 'Pères Noel attribués';

        return new JsonResponse($message, Response::HTTP_OK, [], true);

    }

    public function clearSantasForEvant(Event $event, EntityManagerInterface $em): void
    {
        $eventSantas = $event->getSantas();
        foreach ($eventSantas as $santa) {
            $em->remove($santa);
        }
        $em->flush();
    }
}
