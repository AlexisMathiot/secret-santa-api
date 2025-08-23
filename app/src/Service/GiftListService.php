<?php

namespace App\Service;

use App\Entity\GiftList;
use App\Repository\GiftListRepository;
use App\Entity\User;
use App\Entity\Event;

class GiftListService
{

    public function __construct(
        private GiftListRepository $giftListRepository,
    ) {}

    public function createGiftList(User $user, Event $event): GiftList
    {
        $giftList =
            $this->giftListRepository->findOneBy([
                "user" => $user,
                "event" => $event,
            ]) ?? new GiftList();

        return $giftList;
    }
}
