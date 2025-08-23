<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EventService
{

    public function __construct(private EntityManagerInterface $em, private GiftListService $giftListService) {}

    public function addUSerToEvent(
        User $user,
        Event $event
    ): string {

        $event->addUser($user);
        $giftList = $this->giftListService->createGiftList($user, $event);
        $event->addGiftList($giftList);
        $user->addGiftList($giftList);
        $this->em->flush();
        $userName = $user->getUsername();
        $eventName = $event->getName();
        return "L'utilisateur {$userName} à été ajouté a l'événement {$eventName}";
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
