<?php

namespace App\Controller;

use App\Dto\EventUpdateDto;
use App\Dto\EventCreateDto;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\EventService;
use App\Service\EventValidationService;
use App\Service\InvitationService;
use App\Service\SantaService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route("/api/events")]
#[
    OA\Tag(
        name: "Gestion des événements",
        description: "CRUD et gestion des événements",
    ),
]
class EventController extends AbstractController
{
    public function __construct(
        private EventService $eventService,
        private InvitationService $invitationService,
        private SantaService $santaService,
        private EventValidationService $validationService,
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
    ) {}

    #[
        Route(
            "/{id}",
            name: "event_detail",
            requirements: ["id" => Requirement::DIGITS],
            methods: ["GET"],
        ),
    ]
    #[
        OA\Get(
            path: "/api/events/{id}",
            summary: 'Détails d\'un événement',
            description: 'Récupère les détails complets d\'un événement spécifique',
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            description: 'Identifiant de l\'événement',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: 'Détails de l\'événement récupérés avec succès',
            content: new OA\JsonContent(
                ref: new Model(type: Event::class, groups: ["eventDetail"]),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: 'Accès interdit - Vous n\'avez pas les droits pour voir cet événement',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Événement non trouvé",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Event not found",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function detailEvent(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted("view", $event);

        $jsonEvent = $this->serializer->serialize($event, "json", [
            "groups" => "eventDetail",
        ]);

        return new JsonResponse($jsonEvent, Response::HTTP_OK, [], true);
    }

    #[Route("", name: "event_list", methods: ["GET"])]
    #[
        OA\Get(
            path: "/api/events",
            summary: "Liste tous les événements",
            description: "Récupère la liste complète de tous les événements du système",
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Liste des événements récupérée avec succès",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    ref: new Model(type: Event::class, groups: ["eventDetail"]),
                ),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function listEvent(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();

        $jsonEvents = $this->serializer->serialize($events, "json", [
            "groups" => "eventDetail",
        ]);

        return new JsonResponse($jsonEvents, Response::HTTP_OK, [], true);
    }

    #[Route("", name: "event_create", methods: ["POST"])]
    #[
        OA\Post(
            path: "/api/events",
            summary: "Créer un nouvel événement",
            description: 'Crée un nouvel événement avec l\'utilisateur connecté comme organisateur',
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\RequestBody(
            description: "Données de l'événement à créer",
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: EventCreateDto::class),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 201,
            description: "Événement créé avec succès",
            content: new OA\JsonContent(
                ref: new Model(type: Event::class, groups: ["eventDetail"]),
            ),
            headers: [
                new OA\Header(
                    header: "Location",
                    description: 'URL de l\'événement créé',
                    schema: new OA\Schema(type: "string"),
                ),
            ],
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Données invalides",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "errors",
                        type: "array",
                        items: new OA\Items(type: "string"),
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function addEvent(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            EventCreateDto::class,
            "json",
        );

        // Validation du DTO
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] =
                    $error->getPropertyPath() . ": " . $error->getMessage();
            }
            return new JsonResponse(
                ["errors" => $errorMessages],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $event = new Event();
        $event->setName($dto->name);

        $event->setOrganizer($user);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $jsonEvent = $this->serializer->serialize($event, "json", [
            "groups" => "eventDetail",
        ]);

        $location = $urlGenerator->generate(
            "event_detail",
            ["id" => $event->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse(
            $jsonEvent,
            Response::HTTP_CREATED,
            ["Location" => $location],
            true,
        );
    }

    #[Route("/{id}", name: "api_event_update", methods: ["PUT"])]
    #[
        OA\Put(
            summary: "Modifier un événement",
            description: 'Modifie un événement existant. Seuls les organisateurs de l\'événement et les administrateurs peuvent effectuer cette modification.',
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            description: 'Identifiant unique de l\'événement à modifier',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", minimum: 1),
        ),
    ]
    #[
        OA\RequestBody(
            description: 'Nouvelles données de l\'événement',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: EventUpdateDto::class),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Événement modifié avec succès",
            content: new OA\JsonContent(
                ref: new Model(type: Event::class, groups: ["eventDetail"]),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Données invalides",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    "error" => new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Organisateur non trouvé",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: "Permissions insuffisantes",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    "error" => new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Permissions insuffisantes",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Événement non trouvé",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    "error" => new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Événement non trouvé",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function updateEvent(
        int $id,
        #[MapRequestPayload] EventUpdateDto $updateEventDto,
        EventRepository $eventRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
    ): JsonResponse {
        // Récupération de l'événement
        $event = $eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(
                ["error" => "Événement non trouvé"],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérification des permissions
        $isAdmin = in_array("ROLE_ADMIN", $currentUser->getRoles());
        $isOrganizer = $event->getOrganizer() === $currentUser;

        if (!$isAdmin && !$isOrganizer) {
            return new JsonResponse(
                [
                    "error" =>
                        'Vous n\'avez pas les permissions pour modifier cet événement',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Mise à jour du nom si fourni
        if ($updateEventDto->getName() !== null) {
            $event->setName($updateEventDto->getName());
        }

        // Mise à jour de l'organisateur (seulement pour les admins)
        if ($updateEventDto->getOrganizerId() !== null) {
            if (!$isAdmin) {
                return new JsonResponse(
                    [
                        "error" =>
                            'Seuls les administrateurs peuvent changer l\'organisateur d\'un événement',
                    ],
                    Response::HTTP_FORBIDDEN,
                );
            }

            $newOrganizer = $userRepository->find(
                $updateEventDto->getOrganizerId(),
            );
            if (!$newOrganizer) {
                return new JsonResponse(
                    [
                        "error" => "Organisateur non trouvé",
                    ],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $event->setOrganizer($newOrganizer);
        }

        $entityManager->flush();

        // Sérialisation de l'événement mis à jour
        $jsonEvent = $serializer->serialize($event, "json", [
            "groups" => ["eventDetail"],
        ]);

        return new JsonResponse($jsonEvent, Response::HTTP_OK, [], true);
    }

    #[
        Route(
            "/add/{userId}/{eventId}",
            name: "add_event_user",
            methods: ["GET"],
        ),
    ]
    #[
        OA\Get(
            path: "/api/events/add/{userId}/{eventId}",
            summary: "Ajouter un utilisateur à un événement",
            description: "Ajoute un utilisateur spécifique à un événement (organisateur uniquement). Si l'utilisateur est déjà inscrit, retourne un conflit.",
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "userId",
            description: 'Identifiant de l\'utilisateur à ajouter',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Parameter(
            name: "eventId",
            description: 'Identifiant de l\'événement',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Utilisateur ajouté avec succès",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "success",
                        type: "boolean",
                        example: true,
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "L'utilisateur Alex a été ajouté à l'événement Noël à Clarat",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Utilisateur ou événement non trouvé",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Utilisateur non trouvé",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 409,
            description: "Utilisateur déjà inscrit à l'événement",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "success",
                        type: "boolean",
                        example: false,
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "L'utilisateur Alex est déjà inscrit à l'événement Noël à Clarat",
                    ),
                    new OA\Property(
                        property: "code",
                        type: "string",
                        example: "ALREADY_REGISTERED",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: 'Accès interdit - Seul l\'organisateur peut ajouter des utilisateurs',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function addUserEvent(
        int $userId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository,
    ): JsonResponse {
        $user = $userRepository->findOneBy(["id" => $userId]);
        $event = $eventRepository->findOneBy(["id" => $eventId]);

        if (!$user) {
            return new JsonResponse(
                ["error" => "Utilisateur non trouvé"],
                Response::HTTP_NOT_FOUND,
            );
        }

        if (!$event) {
            return new JsonResponse(
                ["error" => "Événement non trouvé"],
                Response::HTTP_NOT_FOUND,
            );
        }

        $this->denyAccessUnlessGranted("edit", $event);

        $result = $this->eventService->addUserToEvent($user, $event);

        if (!$result["success"] && $result["code"] === "ALREADY_REGISTERED") {
            return new JsonResponse($result, Response::HTTP_CONFLICT);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[
        Route(
            "/remove/{userId}/{eventId}",
            name: "remove_event_user",
            methods: ["GET"],
        ),
    ]
    #[
        OA\Get(
            path: "/api/events/remove/{userId}/{eventId}",
            summary: 'Retirer un utilisateur d\'un événement',
            description: 'Retire un utilisateur d\'un événement et supprime ses assignations Santa',
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "userId",
            description: 'Identifiant de l\'utilisateur à retirer',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Parameter(
            name: "eventId",
            description: 'Identifiant de l\'événement',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Utilisateur retiré avec succès",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: 'Utilisateur retiré de l\'événement',
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Utilisateur ou événement non trouvé, ou utilisateur non inscrit",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: 'L\'utilisateur n\'est pas inscrit à cet événement',
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: 'Accès interdit - Seul l\'organisateur peut retirer des utilisateurs',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function removeUserEvent(
        int $userId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $userRepository->findOneBy(["id" => $userId]);
        $event = $eventRepository->findOneBy(["id" => $eventId]);

        if (!$user || !$event) {
            return new JsonResponse(
                "Utilisateur ou événement non trouvé",
                Response::HTTP_BAD_REQUEST,
            );
        }

        $this->denyAccessUnlessGranted("edit", $event);

        if (!$event->getUsers()->contains($user)) {
            return new JsonResponse(
                'L\'utilisateur n\'est pas inscrit à cet événement',
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Supprimer les assignations Santa
        $this->santaService->removeSantaAssignmentsForUser($event, $user);

        // Retirer l'utilisateur de l'événement
        $message = $this->eventService->removeUserFromEvent($user, $event);

        return new JsonResponse($message, Response::HTTP_OK);
    }

    #[
        Route(
            "/organizer/{userId}/{eventId}",
            name: "set_organizer",
            methods: ["GET"],
        ),
    ]
    #[
        OA\Get(
            path: "/api/events/organizer/{userId}/{eventId}",
            summary: 'Changer l\'organisateur d\'un événement',
            description: "Définit un nouvel organisateur pour un événement",
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "userId",
            description: "Identifiant du nouvel organisateur",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Parameter(
            name: "eventId",
            description: 'Identifiant de l\'événement',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Organisateur modifié avec succès",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Organisateur modifié",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: "Utilisateur ou événement non trouvé",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Utilisateur ou événement non trouvé",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: 'Accès interdit - Seul l\'organisateur actuel peut changer l\'organisateur',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function setOrganizer(
        int $userId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository,
    ): JsonResponse {
        $user = $userRepository->findOneBy(["id" => $userId]);
        $event = $eventRepository->findOneBy(["id" => $eventId]);

        if (!$user || !$event) {
            return new JsonResponse(
                "Utilisateur ou événement non trouvé",
                Response::HTTP_BAD_REQUEST,
            );
        }

        $this->denyAccessUnlessGranted("edit", $event);

        $event->setOrganizer($user);
        $this->entityManager->flush();

        return new JsonResponse("Organisateur modifié", Response::HTTP_OK);
    }

    #[Route("/{id}", name: "event_delete", methods: ["DELETE"])]
    #[
        OA\Delete(
            path: "/api/events/{id}",
            summary: "Supprimer un événement",
            description: "Supprime définitivement un événement (organisateur uniquement)",
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            description: 'Identifiant de l\'événement à supprimer',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[OA\Response(response: 204, description: "Événement supprimé avec succès")]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: 'Accès interdit - Seul l\'organisateur peut supprimer l\'événement',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Événement non trouvé",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Event not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 500,
            description: "Erreur interne lors de la suppression",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: 'Erreur lors de la suppression de l\'événement',
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function deleteEvent(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted("delete", $event);

        try {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            return new JsonResponse(
                'Erreur lors de la suppression de l\'événement',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route("/set-santa/{id}", name: "user_set_santa", methods: ["GET"])]
    #[
        OA\GET(
            path: "/api/events/set-santa/{id}",
            summary: "Assigner les Père Noël secrets",
            description: 'Lance l\'assignation automatique des Père Noël secrets pour un événement',
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Parameter(
            name: "id",
            description: 'Identifiant de l\'événement',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Assignation des Père Noël secrets réussie",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Père Noël secrets assignés avec succès",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: 'Impossible d\'assigner les Père Noël secrets (pas assez de participants, etc.)',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: 'Il faut au moins 3 participants pour lancer l\'assignation',
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: 'Accès interdit - Seul l\'organisateur peut lancer l\'assignation',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Access Denied",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Événement non trouvé",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Event not found",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function setSanta(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted("edit", $event);

        $result = $this->santaService->assignSecretSanta($event);

        if (!$result["success"]) {
            return new JsonResponse(
                $result["message"],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return new JsonResponse($result["message"], Response::HTTP_OK);
    }

    #[Route("/users-to-invit", name: "event_users_to_invit", methods: ["GET"])]
    #[
        OA\Get(
            path: "/api/events/users-to-invit",
            summary: "Liste des utilisateurs invitables",
            description: "Récupère la liste de tous les utilisateurs qui peuvent être invités à un événement",
            tags: ["Gestion des événements"],
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Liste des utilisateurs invitables récupérée avec succès",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    ref: new Model(
                        type: User::class,
                        groups: ["usersInvitToEvent"],
                    ),
                ),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 401,
            description: "Non authentifié",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "JWT Token not found",
                    ),
                ],
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function listUsersInvitToEvent(
        UserRepository $userRepository,
    ): JsonResponse {
        $users = $userRepository->findAll();
        $usersJson = $this->serializer->serialize($users, "json", [
            "groups" => "usersInvitToEvent",
        ]);

        return new JsonResponse($usersJson, Response::HTTP_OK, [], true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[
        Route(
            "/invitations/{fromUserId}/{toUserId}/{eventId}",
            name: "event_mail_to_invit",
            methods: ["POST"],
        ),
    ]
    public function sendMailInvit(
        int $fromUserId,
        string $toUserId,
        int $eventId,
        UserRepository $userRepository,
        EventRepository $eventRepository,
    ): JsonResponse {
        $sendUser = $userRepository->findOneBy(["id" => $fromUserId]);
        $receiverUser = $userRepository->findOneBy(["id" => $toUserId]);
        $event = $eventRepository->findOneBy(["id" => $eventId]);

        if (!$sendUser || !$receiverUser || !$event) {
            return new JsonResponse(
                "Utilisateur ou événement non trouvé",
                Response::HTTP_BAD_REQUEST,
            );
        }

        $this->denyAccessUnlessGranted("edit", $event);

        // Vérifier si l'invitation peut être envoyée
        $canSend = $this->invitationService->canSendInvitation(
            $receiverUser,
            $sendUser,
            $event,
        );
        if (!$canSend["canSend"]) {
            return new JsonResponse(
                $canSend["message"],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Créer et envoyer l'invitation
        $invitation = $this->invitationService->createInvitation(
            $sendUser,
            $receiverUser,
            $event,
        );
        $this->invitationService->sendInvitationEmail($invitation);

        return new JsonResponse("Invitation envoyée", Response::HTTP_OK);
    }
}
