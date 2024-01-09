<?php

namespace App\DataFixtures;

use App\Entity\Gift;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GiftFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $giftsNames = [
            'Une boule de bain',
            'Une jolie coque de téléphone',
            'Un livre de recettes pour étudiants',
            'Un bon pack de bière',
            'Des tickets cadeaux faits maison',
            'Un chouette carnet',
            'Des douceurs cuisinées par vos soins',
            'Un verre à shot original',
            'Un mouchoir à lunettes imprimé',
            'Un diplôme manuscrit',
            'Une plante',
            'Un kit pour faire pousser vos propres champignons',
            'Un abonnement Netflix',
            'Une carte Pokémon PCA',
        ];

        foreach ($giftsNames as $name) {
            $gift = new Gift();
            $gift->setName($name);
            $manager->persist($gift);
        }

        $manager->flush();

    }
}
