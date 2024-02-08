<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\GiftListRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventController extends AbstractController
{

    #[Route('/api/events/{id}', name: "event_detail", methods: ['GET'])]
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
    public function addEvent(Request                $request,
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
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST);
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
    public function editEvent(Event                  $currentEvent,
                              SerializerInterface    $serializer,
                              Request                $request,
                              ValidatorInterface     $validator,
                              EntityManagerInterface $em): JsonResponse
    {

        $this->denyAccessUnlessGranted('edit', $currentEvent);

        $updateEvent = $serializer->deserialize($request->getContent(),
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
    public function addUserEvent(int                    $userId,
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
            $event->addUser($user);
            $em->flush();
            $userName = $user->getUsername();
            $eventName = $event->getName();
            $message = "L'utilisateur {$userName} a été ajouté a l'évènement {$eventName}";

            return new JsonResponse($message, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Utilisateur ou évènement non trouvé', Response::HTTP_BAD_REQUEST, [], true);

    }

    #[Route('api/events/remove/{userId}/{eventId}', name: 'remove_event_user', methods: ['GET'])]
    public function removeUserEvent(int                    $userId,
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
    public function setOrganizer(int                    $userId,
                                 int                    $eventId,
                                 UserRepository         $userRepository,
                                 EventRepository        $eventRepository,
                                 EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $userRepository->findOneBy(['id' => $userId]);
        $event = $eventRepository->findOneBy((['id' => $eventId]));

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

    #[Route('/api/events/{idEvent}/{idList}', name: 'add_gift_list_event', methods: ['GET'])]
    public function addGiftListToEvent(EventRepository        $eventRepository,
                                       GiftListRepository     $giftListRepository,
                                       EntityManagerInterface $em,
                                       int                    $idList,
                                       int                    $idEvent
    ): JsonResponse
    {
        $giftList = $giftListRepository->findOneBy(['id' => $idList]);
        $event = $eventRepository->findOneBy(['id' => $idEvent]);

        if ($giftList !== null && $event !== null) {
            $event->addGiftList($giftList);
            $em->flush();

            return new JsonResponse('List ajouté');
        }

        return new JsonResponse('List ou évènement non trouvé');

    }

}
