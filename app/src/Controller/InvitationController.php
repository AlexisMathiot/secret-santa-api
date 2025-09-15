<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Service\InvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Security;

#[OA\Tag(name: "Invitations")]
class InvitationController extends AbstractController
{
    public function __construct(
        private InvitationService $invitationService,
        private SerializerInterface $serializer,
    ) {}

    #[
        Route(
            "/process/invitations/{token}",
            name: "event_invit",
            methods: ["GET"],
        ),
    ]
    #[
        OA\Get(
            path: "/process/invitations/{token}",
            operationId: "processInvitation",
            summary: "Traiter une invitation",
            description: "Traite une invitation en utilisant son token unique",
        ),
    ]
    #[
        OA\Parameter(
            name: "token",
            description: 'Token unique de l\'invitation',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string", example: "abc123def456"),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Invitation traitée avec succès",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Invitation acceptée avec succès",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 400,
            description: 'Erreur lors du traitement de l\'invitation',
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Token invalide ou invitation expirée",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Invitation non trouvée",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Invitation non trouvée",
                    ),
                ],
            ),
        ),
    ]
    public function processInvit(string $token): JsonResponse
    {
        $result = $this->invitationService->processInvitation($token);

        return new JsonResponse($result["message"], $result["statusCode"]);
    }

    #[Route("/invitations/{token}", name: "get_invitation", methods: ["GET"])]
    #[
        OA\Get(
            path: "/invitations/{token}",
            operationId: "getInvitation",
            summary: "Récupérer une invitation",
            description: 'Récupère les détails d\'une invitation en utilisant son token',
        ),
    ]
    #[
        OA\Parameter(
            name: "token",
            description: 'Token unique de l\'invitation',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string", example: "abc123def456"),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: 'Détails de l\'invitation',
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "id",
                        type: "integer",
                        example: 1,
                    ),
                    new OA\Property(
                        property: "userToInvitId",
                        type: "integer",
                        example: 5,
                    ),
                    new OA\Property(
                        property: "userSentInvitId",
                        type: "integer",
                        example: 2,
                    ),
                    new OA\Property(
                        property: "date",
                        type: "string",
                        format: "date-time",
                        example: "2024-01-15T10:30:00Z",
                    ),
                    new OA\Property(
                        property: "eventId",
                        type: "integer",
                        example: 10,
                    ),
                    new OA\Property(
                        property: "token",
                        type: "string",
                        example: "abc123def456",
                    ),
                ],
            ),
        ),
    ]
    #[
        OA\Response(
            response: 404,
            description: "Invitation non trouvée",
            content: new OA\JsonContent(
                type: "string",
                example: "Invitation non trouvée",
            ),
        ),
    ]
    public function getInvitation(string $token): JsonResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (!$invitation) {
            return new JsonResponse(
                "Invitation non trouvée",
                Response::HTTP_NOT_FOUND,
            );
        }

        $invitationData = [
            "id" => $invitation->getId(),
            "userToInvitId" => $invitation->getUserToInvit()->getId(),
            "userSentInvitId" => $invitation->getUserSentInvit()->getId(),
            "date" => $invitation->getDate(),
            "eventId" => $invitation->getEvent()->getId(),
            "token" => $invitation->getToken(),
        ];

        $jsonInvitation = $this->serializer->serialize(
            $invitationData,
            "json",
            ["groups" => "invitation"],
        );

        return new JsonResponse($jsonInvitation, Response::HTTP_OK, [], true);
    }

    #[
        Route(
            "/api/invitations/{eventId}",
            name: "app_event_get_invitation_by_event",
            methods: ["GET"],
        ),
    ]
    #[
        OA\Get(
            path: "/api/invitations/{eventId}",
            operationId: "getInvitationsByEvent",
            summary: 'Récupérer les invitations d\'un événement',
            description: "Récupère toutes les invitations en attente pour un événement spécifique. Nécessite les permissions appropriées.",
        ),
    ]
    #[
        OA\Parameter(
            name: "eventId",
            description: 'ID de l\'événement',
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 10),
        ),
    ]
    #[
        OA\Response(
            response: 200,
            description: "Liste des invitations en attente",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "id",
                            type: "integer",
                            example: 1,
                        ),
                        new OA\Property(
                            property: "userToInvitId",
                            type: "integer",
                            example: 5,
                        ),
                        new OA\Property(
                            property: "userSentInvitId",
                            type: "integer",
                            example: 2,
                        ),
                        new OA\Property(
                            property: "date",
                            type: "string",
                            format: "date-time",
                            example: "2024-01-15T10:30:00Z",
                        ),
                        new OA\Property(
                            property: "eventId",
                            type: "integer",
                            example: 10,
                        ),
                        new OA\Property(
                            property: "token",
                            type: "string",
                            example: "abc123def456",
                        ),
                        new OA\Property(
                            property: "status",
                            type: "string",
                            example: "pending",
                        ),
                    ],
                ),
            ),
        ),
    ]
    #[
        OA\Response(
            response: 403,
            description: "Accès refusé - Permissions insuffisantes",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "error",
                        type: "string",
                        example: "Accès refusé",
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
                type: "string",
                example: "Événement non trouvé",
            ),
        ),
    ]
    #[Security(name: "Bearer")]
    public function getInvitationByEvent(
        int $eventId,
        EventRepository $eventRepository,
    ): JsonResponse {
        $event = $eventRepository->findOneBy(["id" => $eventId]);

        if (!$event) {
            return new JsonResponse(
                "Événement non trouvé",
                Response::HTTP_NOT_FOUND,
            );
        }

        $this->denyAccessUnlessGranted("view", $event);

        $invitations = $this->invitationService->getPendingInvitationsByEvent(
            $event,
        );
        $jsonInvitations = $this->serializer->serialize($invitations, "json", [
            "groups" => "invitation",
        ]);

        return new JsonResponse($jsonInvitations, Response::HTTP_OK, [], true);
    }
}
