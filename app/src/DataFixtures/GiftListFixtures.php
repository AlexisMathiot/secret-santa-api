<?php

namespace App\DataFixtures;

use App\Entity\GiftList;
use App\Repository\GiftRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GiftListFixtures extends Fixture implements DependentFixtureInterface
{


    public function __construct(private readonly GiftRepository $giftRepository, private readonly UserRepository $userRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {

        $gifts = $this->giftRepository->findAll();
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {

            $giftList = new GiftList();
            $user->addGiftList($giftList);
            $giftsIndex = array_rand($gifts, 5);

            foreach ($giftsIndex as $index) {
                $giftList->addGift($gifts[$index]);
            }

            $manager->persist($giftList);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GiftFixtures::class,
            UserFixtures::class
        ];
    }
}
