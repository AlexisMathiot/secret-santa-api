<?php

namespace App\Service;

use App\Entity\GiftList;
use App\Entity\User;
use App\Repository\GiftListRepository;
use App\Repository\SantaRepository;

readonly class UserData
{

    public function __construct(private GiftListRepository $giftListRepository,
                                private SantaRepository    $santaRepository)
    {

    }

    public function userDataToArray(User $user): array
    {

        $userEvents = $user->getEvents();
        $userArray = [
            'id' => $user->getId(),
            'userName' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];
        if ($userEvents->count() > 0) {
            foreach ($userEvents as $k => $event) {
                $giftList = $this->giftListRepository->findOneBy(['event' => $event, 'user' => $user]) ?? new GiftList();
                $eventSanta = $this->santaRepository->findOneBy([
                    'event' => $event,
                    'user' => $user
                ]);

                $eventArray = [
                    'id' => $event->getId(),
                    'name' => $event->getName(),
                    'giftList' => $giftList,
                    'santaOfId' => $eventSanta?->getSanta()->getId(),
                    'santaOf' => $eventSanta?->getSanta()->getUsername(),
                    'santaOfGiftList' => $this->giftListRepository->findOneBy([
                        'event' => $event,
                        'user' => $eventSanta?->getSanta()
                    ])
                ];

                $userArray['events'][$k] = $eventArray;
            }
        }

        return $userArray;
    }
}
