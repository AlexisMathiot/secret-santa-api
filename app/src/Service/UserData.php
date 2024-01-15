<?php

namespace App\Service;

use App\Entity\User;

class UserData
{

    public function userDataToArray(User $user): array
    {
        $giftsArray = [];
        $giftsSantaArray = [];
        $userSanta = $user->getSantaOf();
        $userArray = [
            'id' => $user->getId(),
            'userName' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'userGiftListId' => $user->getGiftList()->getId()
        ];

        if ($user->getGiftList() !== null) {
            $gifts = $user->getGiftList()->getGifts();
            foreach ($gifts as $gift) {
                $giftsArray[] = [$gift->getId(), $gift->getName()];
            }
            $userArray['gifts'] = $giftsArray;
        }

        if ($userSanta !== null) {
            $userArray['SantaOfId'] = $userSanta->getId();
            $userArray['SantaOf'] = $userSanta->getUsername();
            if ($userSanta->getGiftList() !== null) {
                foreach ($userSanta->getGiftList()->getGifts() as $gift) {
                    $giftsSantaArray[] = [$gift->getId(), $gift->getName()];
                }
                $userArray['SantaOfGiftsLists'] = $giftsSantaArray;
            }
        }

        return $userArray;
    }
}