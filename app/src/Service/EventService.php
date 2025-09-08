<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EventService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GiftListService $giftListService,
    ) {}

    public function addUserToEvent(User $user, Event $event): array
    {
        if ($event->getUsers()->contains($user)) {
            return [
                "success" => false,
                "message" => "L'utilisateur {$user->getUsername()} est déjà inscrit à l'événement {$event->getName()}",
                "code" => "ALREADY_REGISTERED",
            ];
        }

        $event->addUser($user);
        $giftList = $this->giftListService->createGiftList($user, $event);
        $event->addGiftList($giftList);
        $user->addGiftList($giftList);
        $this->em->flush();

        return [
            "success" => true,
            "message" => "L'utilisateur {$user->getUsername()} a été ajouté à l'événement {$event->getName()}",
            "code" => "USER_ADDED",
        ];
    }

    public function removeUserFromEvent(User $user, Event $event): string
    {
        $event->removeUser($user);
        $this->em->flush();

        $userName = $user->getUsername();
        $eventName = $event->getName();
        $message = "L'utilisateur {$userName} a été retiré de l'évènement {$eventName}";

        return $message;
    }
}
