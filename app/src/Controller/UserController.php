<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SantaRepository;
use App\Service\UserData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function deleteUserCompte(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var  User $user */
        $eventOrganize = $user->getEventsOrganize();

        if ($eventOrganize->count() > 0) {
            return new JsonResponse('Vous êtes organisateur d\'un évènement, merci de changer l\'organisateur');
        }

        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user/{id}', name: 'api_user_update_surname', methods: 'PUT')]
    public function updateSurname(User $user, Request $request, EntityManagerInterface $em): Response {

        $pseudo =  json_decode($request->getContent(), true)['pseudo'];
        $user->setPseudo($pseudo);
        $em->persist($user);
        $em->flush();

        return $this->json($user, Response::HTTP_OK);
    }
}
