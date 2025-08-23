<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\EventService;
use App\Service\EventValidationService;
use App\Service\InvitationService;
use App\Service\SantaService;
use Doctrine\ORM\EntityManager;
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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/events')]
class EventController extends AbstractController
{
    public function __construct(
        private EventService $eventService,
        private InvitationService $invitationService,
        private SantaService $santaService,
        private EventValidationService $validationService,
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/{id}', name: 'event_detail', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function detailEvent(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $event);

        $jsonEvent = $this->serializer->serialize($event, 'json', [
            'groups' => 'eventDetail',
        ]);

        return new JsonResponse($jsonEvent, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'event_list', methods: ['GET'])]
    public function listEvent(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();

        $jsonEvents = $this->serializer->serialize($events, 'json', [
            'groups' => 'eventDetail',
        ]);

        return new JsonResponse($jsonEvents, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'event_create', methods: ['POST'])]
    public function addEvent(
        Request $request,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $event = $this->serializer->deserialize(
            $request->getContent(),
            Event::class,
            'json'
        );

        $event->setOrganizer($user);

        $validation = $this->validationService->validateEvent($event);
        if (!$validation['isValid']) {
            return new JsonResponse($validation['errors'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $jsonEvent = $this->serializer->serialize($event, 'json', [
            'groups' => 'eventDetail',
        ]);

        $location = $urlGenerator->generate(
            'event_detail',
            ['id' => $event->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $jsonEvent,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true
        );
    }

    #[Route('/{id}', name: 'edit_event', methods: ['PUT'])]
    public function editEvent(Event $currentEvent, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $currentEvent);

        $this->serializer->deserialize(
            $request->getContent(),
            Event::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentEvent]
        );

        $validation = $this->validationService->validateEvent($currentEvent);
        if (!$validation['isValid']) {
            return new JsonResponse(
                $this->serializer->serialize($validation['errors'], 'json'),
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->entityManager->persist($currentEvent);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/add/{userId}/{eventId}', name: 'add_event_user', methods: ['GET'])]
    public function addUserEvent(
        int $userId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository
    ): JsonResponse {
        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        if (!$user || !$event) {
            return new JsonResponse(
                'Utilisateur ou événement non trouvé',
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->denyAccessUnlessGranted('edit', $event);

        $message = $this->eventService->addUserToEvent($user, $event);
        return new JsonResponse($message, Response::HTTP_OK);
    }

    #[Route('/remove/{userId}/{eventId}', name: 'remove_event_user', methods: ['GET'])]
    public function removeUserEvent(
        int $userId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository,
        EntityManager $em
    ): JsonResponse {
        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        if (!$user || !$event) {
            return new JsonResponse(
                'Utilisateur ou événement non trouvé',
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->denyAccessUnlessGranted('edit', $event);

        if (!$event->getUsers()->contains($user)) {
            return new JsonResponse(
                'L\'utilisateur n\'est pas inscrit à cet événement',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Supprimer les assignations Santa
        $this->santaService->removeSantaAssignmentsForUser($event, $user);

        // Retirer l'utilisateur de l'événement
        $message = $this->eventService->removeUserFromEvent($user, $event, $em);

        return new JsonResponse($message, Response::HTTP_OK);
    }

    #[Route('/organizer/{userId}/{eventId}', name: 'set_organizer', methods: ['GET'])]
    public function setOrganizer(
        int $userId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository
    ): JsonResponse {
        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        if (!$user || !$event) {
            return new JsonResponse(
                'Utilisateur ou événement non trouvé',
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->denyAccessUnlessGranted('edit', $event);

        $event->setOrganizer($user);
        $this->entityManager->flush();

        return new JsonResponse('Organisateur modifié', Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'event_delete', methods: ['DELETE'])]
    public function deleteEvent(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted('delete', $event);

        try {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            return new JsonResponse(
                'Erreur lors de la suppression de l\'événement',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/set-santa/{id}', name: 'user_set_santa', methods: ['POST'])]
    public function setSanta(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $event);

        $result = $this->santaService->assignSecretSanta($event);

        if (!$result['success']) {
            return new JsonResponse($result['message'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result['message'], Response::HTTP_OK);
    }

    #[Route('/users-to-invit', name: 'event_users_to_invit', methods: ['GET'])]
    public function listUsersInvitToEvent(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        $usersJson = $this->serializer->serialize($users, 'json', [
            'groups' => 'usersInvitToEvent',
        ]);

        return new JsonResponse($usersJson, Response::HTTP_OK, [], true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/invitations/{fromUserId}/{toUserId}/{eventId}', name: 'event_mail_to_invit', methods: ['POST'])]
    public function sendMailInvit(
        int $fromUserId,
        string $toUserId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository
    ): JsonResponse {
        $sendUser = $userRepository->findOneBy(['id' => $fromUserId]);
        $receiverUser = $userRepository->findOneBy(['id' => $toUserId]);
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        if (!$sendUser || !$receiverUser || !$event) {
            return new JsonResponse(
                'Utilisateur ou événement non trouvé',
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->denyAccessUnlessGranted('edit', $event);

        // Vérifier si l'invitation peut être envoyée
        $canSend = $this->invitationService->canSendInvitation($receiverUser, $sendUser, $event);
        if (!$canSend['canSend']) {
            return new JsonResponse($canSend['message'], Response::HTTP_BAD_REQUEST);
        }

        // Créer et envoyer l'invitation
        $invitation = $this->invitationService->createInvitation($sendUser, $receiverUser, $event);
        $this->invitationService->sendInvitationEmail($invitation);

        return new JsonResponse('Invitation envoyée', Response::HTTP_OK);
    }
}
