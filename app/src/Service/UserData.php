<?php

namespace App\Service;

use App\Entity\GiftList;
use App\Entity\User;
use App\Repository\GiftListRepository;
use App\Repository\SantaRepository;

readonly class UserData
{
    public function __construct(
        private GiftListRepository $giftListRepository,
        private SantaRepository $santaRepository,
    ) {}

    public function userDataToArray(User $user): array
    {
        $userArray = [
            "id" => $user->getId(),
            "userName" => $user->getUsername(),
            "pseudo" => $user->getPseudo(),
            "email" => $user->getEmail(),
            "roles" => $user->getRoles(),
            "events" => [],
            "isOrganizerOfEvent" => false,
            "organizedEventIds" => [],
        ];

        // Gestion des événements où l'utilisateur participe
        $userEvents = $user->getEvents();
        if ($userEvents->count() > 0) {
            foreach ($userEvents as $event) {
                $userArray["events"][] = $this->buildEventArray($event, $user);
            }
        }

        // Gestion des événements organisés par l'utilisateur
        $organizedEvents = $user->getEventsOrganize();
        if ($organizedEvents->count() > 0) {
            $userArray["isOrganizerOfEvent"] = true;
            $userArray["organizedEventIds"] = $organizedEvents
                ->map(fn($event) => $event->getId())
                ->toArray();
        }

        return $userArray;
    }

    private function buildEventArray($event, User $user): array
    {
        // Récupération de la gift list de l'utilisateur pour cet événement
        $giftList =
            $this->giftListRepository->findOneBy([
                "event" => $event,
                "user" => $user,
            ]) ?? new GiftList();

        // Récupération des informations Santa
        $eventSanta = $this->santaRepository->findOneBy([
            "event" => $event,
            "user" => $user,
        ]);

        $eventArray = [
            "id" => $event->getId(),
            "name" => $event->getName(),
            "giftList" => $this->serializeGiftList($giftList),
            "santaOfId" => null,
            "santaOf" => null,
            "santaOfPseudo" => null,
            "santaOfGiftList" => null,
        ];

        // Si l'utilisateur est le Santa de quelqu'un
        if ($eventSanta && $eventSanta->getSanta()) {
            $santaUser = $eventSanta->getSanta();
            $eventArray["santaOfId"] = $santaUser->getId();
            $eventArray["santaOf"] = $santaUser->getUsername();
            $eventArray["santaOfPseudo"] = $santaUser->getPseudo();

            $santaGiftList = $this->giftListRepository->findOneBy([
                "event" => $event,
                "user" => $santaUser,
            ]);

            if ($santaGiftList) {
                $eventArray["santaOfGiftList"] = $this->serializeGiftList(
                    $santaGiftList,
                );
            }
        }

        return $eventArray;
    }

    private function serializeGiftList(GiftList $giftList): array
    {
        return [
            "id" => $giftList->getId(),
            // Ajoutez d'autres propriétés selon vos besoins
        ];
    }
}
