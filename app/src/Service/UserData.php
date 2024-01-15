<?php

namespace App\Service;

use App\Entity\User;

class UserData
{

    public function userDataToArray(User $user): array
    {
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
            $userArray['gifts'] = $gifts;
        }

        if ($userSanta !== null) {
            $userArray['SantaOfId'] = $userSanta->getId();
            $userArray['SantaOf'] = $userSanta->getUsername();
            if ($userSanta->getGiftList() !== null) {

                $userArray['SantaOfGiftsLists'] = $userSanta->getGiftList()->getGifts();
            }
        }

        return $userArray;
    }
}
