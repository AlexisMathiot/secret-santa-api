<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\GiftList;
use App\Entity\Invitation;
use App\Entity\Santa;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\GiftListRepository;
use App\Repository\InvitationRepository;
use App\Repository\SantaRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventController extends AbstractController
{

    #[Route('/api/events/{id}', name: "event_detail", requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function detailEvent(Event $event, SerializerInterface $serializer): JsonResponse
    {
        $jsonEvent = $serializer->serialize($event, 'json', ['groups' => 'eventDetail']);
        return new JsonResponse($jsonEvent, Response::HTTP_OK, [], true);
    }

    #[Route('api/events', name: "event_list", methods: ['GET'])]
    public function listEvent(SerializerInterface $serializer, EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();

        $jsonEvents = $serializer->serialize($events, 'json', ['groups' => 'eventDetail']);

        return new JsonResponse($jsonEvents, Response::HTTP_OK, [], true);
    }

    #[Route('/api/events', name: "event_create", methods: ['POST'])]
    public function addEvent(
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface  $urlGenerator,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $event = $serializer->deserialize($request->getContent(), Event::class, 'json');
        $event->setOrganizer($user);
        $errors = $validator->validate($event);

        if ($errors->count() > 0) {
            $messages = [];
            foreach ($errors as $violation) {
                $messages[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return new JsonResponse($messages, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($event);
        $em->flush();

        $jsonEvent = $serializer->serialize($event, 'json', ['groups' => 'eventDetail']);
        $location = $urlGenerator->generate(
            'event_detail',
            ['id' => $event->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($jsonEvent, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('api/events/{id}', name: 'edit_event', methods: ['PUT'])]
    public function editEvent(
        Event                  $currentEvent,
        SerializerInterface    $serializer,
        Request                $request,
        ValidatorInterface     $validator,
        EntityManagerInterface $em
    ): JsonResponse
    {

        $this->denyAccessUnlessGranted('edit', $currentEvent);

        $updateEvent = $serializer->deserialize(
            $request->getContent(),
            Event::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentEvent]
        );

        $errors = $validator->validate($currentEvent);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST);
        }

        $em->persist($updateEvent);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    #[Route('api/events/add/{userId}/{eventId}', name: 'add_event_user', methods: ['GET'])]
    public function addUserEvent(
        int                    $userId,
        int                    $eventId,
        UserRepository         $userRepository,
        EventRepository        $eventRepository,
        EntityManagerInterface $em,
        GiftListRepository     $giftListRepository
    ): JsonResponse
    {

        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        $this->denyAccessUnlessGranted('edit', $event);

        if ($user !== null && $event !== null) {

            $message = $this->addUSerToEvent($user, $event, $giftListRepository, $em);

            return new JsonResponse($message, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Utilisateur ou évènement non trouvé', Response::HTTP_BAD_REQUEST, [], true);
    }

    public function addUSerToEvent(User $user, Event $event, GiftListRepository $giftListRepository, EntityManagerInterface $em): string
    {
        $giftList = $giftListRepository->findOneBy(['user' => $user, 'event' => $event]) ?? new GiftList();
        $event->addUser($user);
        $event->addGiftList($giftList);
        $user->addGiftList($giftList);
        $em->flush();
        $userName = $user->getUsername();
        $eventName = $event->getName();
        return "L'utilisateur {$userName} à été ajouté a l'événement {$eventName}";
    }

    #[Route('api/events/remove/{userId}/{eventId}', name: 'remove_event_user', methods: ['GET'])]
    public function removeUserEvent(
        int                    $userId,
        int                    $eventId,
        UserRepository         $userRepository,
        EventRepository        $eventRepository,
        SantaRepository        $santaRepository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        $this->denyAccessUnlessGranted('edit', $event);

        if ($user !== null && $event !== null) {
            $santas = $santaRepository->findByEventSantaandUser($event, $user);
            if (count($santas) > 0) {
                foreach ($santas as $santa) {
                    $em->remove($santa);
                }
                $em->flush();
            }

            if ($event->getUsers()->contains($user)) {
                $event->removeUser($user);
                $em->flush();

                $userName = $user->getUsername();
                $eventName = $event->getName();
                $message = "L'utilisateur {$userName} a été retiré de l'évènement {$eventName}";

                return new JsonResponse($message, Response::HTTP_OK, [], true);
            }
        }

        return new JsonResponse('Utilisateur ou évènement non trouvé', Response::HTTP_BAD_REQUEST, [], true);
    }

    #[Route('api/events/organizer/{userId}/{eventId}', name: 'set_organizer', methods: ['GET'])]
    public function setOrganizer(
        int                    $userId,
        int                    $eventId,
        UserRepository         $userRepository,
        EventRepository        $eventRepository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        $this->denyAccessUnlessGranted('edit', $event);

        if ($user !== null && $event !== null) {
            $event->setOrganizer($user);
            $em->flush();

            return new JsonResponse('Organisateur modifié', Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Utilisateur ou évènement non trouvé', Response::HTTP_BAD_REQUEST, [], true);
    }

    /**
     * @throws ORMException
     */
    #[Route('/api/events/{id}', name: 'event_delete', methods: ['DELETE'])]
    public function deleteEvent(Event $event, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('delete', $event);
        try {
            $em->remove($event);
            $em->flush();
        } catch (ORMException $e) {
            throw new ORMException('erreur : ' . $e);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/users/setsanta/{id}', name: "user_set_santa", methods: ['GET'])]
    public function setSanta(EntityManagerInterface $em, Event $event): JsonResponse
    {

        $this->denyAccessUnlessGranted('delete', $event);

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

    #[Route('api/events/users-to-invit', name: 'event_users_to_invit', methods: ['GET'])]
    public function listUsersInvitToEvent(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $users = $userRepository->findAll();
        $usersJson = $serializer->serialize($users, 'json', ['groups' => 'usersInvitToEvent']);

        return new JsonResponse($usersJson, Response::HTTP_OK, [], true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('api/events/invit/{fromUserId}/{toUserId}/{eventId}', name: 'event_mail_to_invit', methods: ['GET'])]
    public function sendMailInvit(
        int                     $fromUserId,
        string                  $toUserId,
        int                     $eventId,
        InvitationRepository    $invitationRepository,
        UserRepository          $userRepository,
        EventRepository         $eventRepository,
        EntityManagerInterface  $entityManager,
        MailerService           $mail,
        TokenGeneratorInterface $tokenGenerator
    ): JsonResponse
    {
        $sendUser = $userRepository->findOneBy(['id' => $fromUserId]);
        $receiverUser = $userRepository->findOneBy(['id' => $toUserId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        if ($sendUser !== null && $receiverUser !== null && $event !== null) {
            if ($event->getUsers()->contains($receiverUser)) {
                return new JsonResponse("L'utilisateur {$receiverUser->getUsername()} est déjà inscrit à l'évènement {$event->getName()}");
            }

            $invitation = $invitationRepository->findOneBy(['userToInvit' => $receiverUser, 'userSentInvit' => $sendUser, 'event' => $event]);
            if ($invitation !== null && $invitation->getDate()->diff(new \DateTime(), true)->days < 7) {
                return new JsonResponse(
                    "Une invitation pour l'utilisateur {$receiverUser->getUsername()} à l'évènement {$event->getName()} éxiste déja"
                );
            }

            $invitation = new Invitation();
            $invitation->setEvent($event);
            $invitation->setUserSentInvit($sendUser);
            $invitation->setUserToInvit($receiverUser);
            $invitation->setDate(new \DateTime());
            $invitation->setToken($tokenGenerator->generateToken());
            $entityManager->persist($invitation);
            $entityManager->flush();
            $baseUrl = $this->getParameter('app.front_base_url');
            $url = $baseUrl . '/invit/' . $invitation->getToken();
            $context = compact('url', 'sendUser', 'receiverUser', 'event', 'invitation');
            $mail->send(
                'no-reply@domain.fr',
                $receiverUser->getEmail(),
                'Invitation a l\'évènement ' . $event->getName(),
                'invit_to_event',
                $context
            );
            return new JsonResponse("Invitation envoyé", Response::HTTP_OK, [], true);
        }
        return new JsonResponse("Utilisateur ou évènement non trouvé", Response::HTTP_BAD_REQUEST, [], true);
    }

    #[Route('process/invit/{token}', name: 'event_invit', methods: ['GET'])]
    public function processInvit(
        string                 $token,
        InvitationRepository   $invitationRepository,
        GiftListRepository     $giftListRepository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $invitation = $invitationRepository->findOneBy(['token' => $token]);
        if ($invitation !== null) {
            if ($invitation->getDate()->diff(new \DateTime(), true)->days > 7) {
                $em->remove($invitation);
                $em->flush();
                return new JsonResponse(
                    "La date de validation du lien est dépassée merci de renvoyer une invitation",
                    Response::HTTP_OK,
                    [],
                    true
                );
            }
            $userToInvit = $invitation->getUserToInvit();
            $event = $invitation->getEvent();
            $message = $this->addUSerToEvent($userToInvit, $event, $giftListRepository, $em);
            $em->remove($invitation);
            $em->flush();
            return new JsonResponse($message, Response::HTTP_OK, [], true);
        }
        return new JsonResponse('Invitation non trouvée', Response::HTTP_NOT_FOUND, [], true);
    }

    #[Route('/invitations/{token}')]
    public function getInvitation(
        string               $token,
        InvitationRepository $invitationRepository,
        SerializerInterface  $serializer
    ): JsonResponse
    {
        $invitation = $invitationRepository->findOneBy(['token' => $token]);
        if ($invitation !== null) {
            $invitationToArray = [
                'id' => $invitation->getId(),
                'userToInvitId' => $invitation->getUserToInvit()->getId(),
                'userSentInvitId' => $invitation->getUserSentInvit()->getId(),
                'date' => $invitation->getDate(),
                'eventId' => $invitation->getEvent()->getId(),
                'token' => $invitation->getToken()
            ];
            $jsonInvitation = $serializer->serialize($invitationToArray, 'json', ['groups' => 'invitation']);
            return new JsonResponse($jsonInvitation, Response::HTTP_OK, [], true);
        }
        return new JsonResponse('Invitation non trouvée', Response::HTTP_NOT_FOUND, [], true);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('api/invitations/{eventId}', methods: ['GET'])]
    public function getInvitationByEvent(
        EventRepository     $eventRepository,
        int                 $eventId,
        SerializerInterface $serializer,
        InvitationRepository $invitationRepository
    ): JsonResponse
    {
        $event = $eventRepository->findOneBy(['id' => $eventId]);
        if ($event !== null) {
            $invitations = $invitationRepository->selectPendingInvitationByEvent($event);
            $jsonInvitations = $serializer->serialize($invitations, 'json', ['groups' => 'invitation']);
            return new JsonResponse($jsonInvitations, Response::HTTP_OK, [], true);
        }
        return new JsonResponse('Evènement non trouvé', Response::HTTP_NOT_FOUND, [], true);
    }
}
