<?php

namespace App\DataFixtures;

use App\Entity\Invitation;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InvitationFixture extends Fixture implements DependentFixtureInterface
{

    public function __construct(
        private readonly UserRepository  $userRepository,
        private readonly EventRepository $eventRepository
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->userRepository->findAll();
        $events = $this->eventRepository->findAll();
        $datetimes = [new \DateTime('now'), new \DateTime('8 days ago')];

        foreach ($events as $event) {
            for ($i = 0; $i < count($users); $i++) {
                $invitation = new Invitation();
                $invitation->setUserToInvit($users[($i + 1) % count($users)]);
                $invitation->setEvent($event);
                $invitation->setUserSentInvit($users[$i]);
                $invitation->setDate($datetimes[$i % count($datetimes)]);
                $manager->persist($invitation);
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EventFxitures::class,
            UserFixtures::class
        ];
    }
}