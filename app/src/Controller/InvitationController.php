<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Service\InvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class InvitationController extends AbstractController
{
    public function __construct(
        private InvitationService $invitationService,
        private SerializerInterface $serializer
    ) {}

    #[Route('/process/invitations/{token}', name: 'event_invit', methods: ['GET'])]
    public function processInvit(string $token): JsonResponse
    {
        $result = $this->invitationService->processInvitation($token);

        return new JsonResponse(
            $result['message'],
            $result['statusCode']
        );
    }

    #[Route('/invitations/{token}', name: 'get_invitation', methods: ['GET'])]
    public function getInvitation(string $token): JsonResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (!$invitation) {
            return new JsonResponse(
                'Invitation non trouvée',
                Response::HTTP_NOT_FOUND
            );
        }

        $invitationData = [
            'id' => $invitation->getId(),
            'userToInvitId' => $invitation->getUserToInvit()->getId(),
            'userSentInvitId' => $invitation->getUserSentInvit()->getId(),
            'date' => $invitation->getDate(),
            'eventId' => $invitation->getEvent()->getId(),
            'token' => $invitation->getToken(),
        ];

        $jsonInvitation = $this->serializer->serialize(
            $invitationData,
            'json',
            ['groups' => 'invitation']
        );

        return new JsonResponse($jsonInvitation, Response::HTTP_OK, [], true);
    }

    #[Route('/api/invitations/{eventId}', name: 'app_event_get_invitation_by_event', methods: ['GET'])]
    public function getInvitationByEvent(
        int $eventId,
        EventRepository $eventRepository
    ): JsonResponse {
        $event = $eventRepository->findOneBy(['id' => $eventId]);

        if (!$event) {
            return new JsonResponse(
                'Événement non trouvé',
                Response::HTTP_NOT_FOUND
            );
        }

        $this->denyAccessUnlessGranted('view', $event);

        $invitations = $this->invitationService->getPendingInvitationsByEvent($event);
        $jsonInvitations = $this->serializer->serialize($invitations, 'json', [
            'groups' => 'invitation',
        ]);

        return new JsonResponse($jsonInvitations, Response::HTTP_OK, [], true);
    }
}
