<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 12; $i++) {
            $ville = new Ville();
            $ville->setNom($faker->city);
            $ville->setCodePostal(str_pad((string) $faker->numberBetween(1000, 32000), 5, '0', STR_PAD_LEFT));

            $manager->persist($ville);
        }

        $manager->flush();
    }
}
