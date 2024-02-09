<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\GiftListRepository;

class UserData
{

    public function __construct(private readonly GiftListRepository $giftListRepository)
    {

    }

    public function userDataToArray(User $user): array
    {
        $userSanta = $user->getSantaOf();
        $userEvents = $user->getEvents();
        $userArray = [
            'id' => $user->getId(),
            'userName' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        if ($userEvents->count() > 0) {
            foreach ($userEvents as $k => $event) {
                $i = 0;
                $giftList = $this->giftListRepository->findOneBy([
                    'event' => $event,
                    'user' => $user
                ]);
                $eventArray = [
                    'id' => $event->getId(),
                    'name' => $event->getName(),
                    'giftList' => $giftList,
                ];
                $userArray['events'][$k] = $eventArray;
            }
        }

        return $userArray;
    }
}
