<?php

namespace App\DataFixtures;

use App\Entity\GiftList;
use App\Repository\GiftRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GiftListFixtures extends Fixture implements DependentFixtureInterface
{


    public function __construct(private readonly GiftRepository $giftRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {

        $gifts = $this->giftRepository->findAll();

        for ($i = 0; $i < 5; $i++) {

            $giftList = new GiftList();
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
        ];
    }
}
