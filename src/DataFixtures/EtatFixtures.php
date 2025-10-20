<?php

namespace App\DataFixtures;

use App\Entity\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EtatFixtures extends Fixture
{
    public const ETAT_VALUES = [
        Etat::Creee,
        Etat::Ouverte,
        Etat::Cloturee,
        Etat::Activite,
        Etat::Passee,
        Etat::Annulee,
    ];

    public function load(ObjectManager $manager): void
    {

    }
}
