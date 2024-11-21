<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\GiftList;
use App\Repository\EventRepository;
use App\Repository\GiftRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventFxitures extends Fixture implements DependentFixtureInterface
{


    public function __construct(private readonly GiftRepository  $giftRepository,
                                private readonly UserRepository  $userRepository,
                                private readonly EventRepository $eventRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {

        $gifts = $this->giftRepository->findAll();
        $users = $this->userRepository->findAll();
        $admin = $this->userRepository->findOneBy(['username' => 'Admin']);

        $eventsName = ['Noel a Clarat', 'Noel a Coutras', 'Anniversaire Pépé'];

        foreach ($eventsName as $name) {
            $event = new Event();
            $event->setName($name);
            $manager->persist($event);
        }
        $manager->flush();

        $events = $this->eventRepository->findAll();
        foreach ($events as $event) {
            $event->setOrganizer($admin);
            foreach ($users as $user) {
                $giftList = new GiftList();
                $user->addGiftList($giftList);
                $giftList->setEvent($event);
                $event->addUser($user);
                $giftsIndex = array_rand($gifts, 5);

                foreach ($giftsIndex as $index) {
                    $giftList->addGift($gifts[$index]);
                }

                $manager->persist($giftList);
            }
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
