<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\User;
use App\Repository\GiftListRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class InvitationService
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager,
        private TokenGeneratorInterface $tokenGenerator,
        private MailerService $mailerService,
        private EventService $eventService,
        private GiftListRepository $giftListRepository,
        private GiftListService $giftListService,
        private string $frontBaseUrl
    ) {}

    public function canSendInvitation(User $receiverUser, User $senderUser, Event $event): array
    {
        // Vérifier si l'utilisateur est déjà inscrit
        if ($event->getUsers()->contains($receiverUser)) {
            return [
                'canSend' => false,
                'message' => "L'utilisateur {$receiverUser->getUsername()} est déjà inscrit à l'événement {$event->getName()}"
            ];
        }

        // Vérifier s'il y a déjà une invitation récente
        $existingInvitation = $this->invitationRepository->findOneBy([
            'userToInvit' => $receiverUser,
            'userSentInvit' => $senderUser,
            'event' => $event,
        ]);

        if (
            $existingInvitation !== null &&
            $existingInvitation->getDate()->diff(new \DateTime(), true)->days < 7
        ) {
            return [
                'canSend' => false,
                'message' => "Une invitation pour l'utilisateur {$receiverUser->getUsername()} à l'événement {$event->getName()} existe déjà"
            ];
        }

        return ['canSend' => true, 'message' => ''];
    }

    public function createInvitation(User $senderUser, User $receiverUser, Event $event): Invitation
    {
        $invitation = new Invitation();
        $invitation->setEvent($event);
        $invitation->setUserSentInvit($senderUser);
        $invitation->setUserToInvit($receiverUser);
        $invitation->setDate(new \DateTime());
        $invitation->setToken($this->tokenGenerator->generateToken());

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return $invitation;
    }

    public function sendInvitationEmail(Invitation $invitation): void
    {
        $url = $this->frontBaseUrl . '/invit/' . $invitation->getToken();

        $context = [
            'url' => $url,
            'sendUser' => $invitation->getUserSentInvit(),
            'receiverUser' => $invitation->getUserToInvit(),
            'event' => $invitation->getEvent(),
            'invitation' => $invitation,
        ];

        $this->mailerService->send(
            'no-reply@domain.fr',
            $invitation->getUserToInvit()->getEmail(),
            'Invitation à l\'événement ' . $invitation->getEvent()->getName(),
            'invit_to_event',
            $context
        );
    }

    public function processInvitation(string $token): array
    {
        $invitation = $this->invitationRepository->findOneBy(['token' => $token]);

        if ($invitation === null) {
            return [
                'success' => false,
                'message' => 'Invitation non trouvée',
                'statusCode' => 404
            ];
        }

        // Vérifier si l'invitation n'est pas expirée
        if ($invitation->getDate()->diff(new \DateTime(), true)->days > 7) {
            $this->entityManager->remove($invitation);
            $this->entityManager->flush();

            return [
                'success' => false,
                'message' => 'La date de validation du lien est dépassée merci de renvoyer une invitation',
                'statusCode' => 400
            ];
        }

        // Ajouter l'utilisateur à l'événement
        $userToInvit = $invitation->getUserToInvit();
        $event = $invitation->getEvent();

        $message = $this->eventService->addUserToEvent($userToInvit, $event);

        // Supprimer l'invitation
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => $message,
            'statusCode' => 200
        ];
    }

    public function getInvitationByToken(string $token): ?Invitation
    {
        return $this->invitationRepository->findOneBy(['token' => $token]);
    }

    public function getPendingInvitationsByEvent(Event $event): array
    {
        return $this->invitationRepository->selectPendingInvitationByEvent($event);
    }
}
