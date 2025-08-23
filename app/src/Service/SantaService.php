<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Santa;
use App\Entity\User;
use App\Repository\SantaRepository;
use Doctrine\ORM\EntityManagerInterface;

class SantaService
{
    public function __construct(
        private SantaRepository $santaRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function assignSecretSanta(Event $event): array
    {
        $users = $event->getUsers()->toArray();
        $count = count($users);

        if ($count <= 1) {
            return [
                'success' => false,
                'message' => 'Il doit y avoir au moins 2 personnes participant à l\'événement'
            ];
        }

        // Nettoyer les assignations existantes
        $this->clearSantasForEvent($event);

        // Mélanger les utilisateurs
        shuffle($users);

        // Créer les assignations en cercle
        foreach ($users as $i => $user) {
            $santa = new Santa();
            $santa->setEvent($event);
            $santa->setUser($user);
            $santa->setSanta($users[($i + 1) % $count]); // L'utilisateur suivant est son destinataire (boucle)
            $this->entityManager->persist($santa);
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Pères Noël attribués'
        ];
    }

    public function clearSantasForEvent(Event $event): void
    {
        $eventSantas = $event->getSantas();
        foreach ($eventSantas as $santa) {
            $this->entityManager->remove($santa);
        }
        $this->entityManager->flush();
    }

    public function removeSantaAssignmentsForUser(Event $event, User $user): void
    {
        $santas = $this->santaRepository->findByEventSantaandUser($event, $user);

        foreach ($santas as $santa) {
            $this->entityManager->remove($santa);
        }

        $this->entityManager->flush();
    }
}
