<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\DataFixtures\VilleFixtures;

class LieuFixtures extends Fixture implements DependentFixtureInterface
{
    public const LIEU_REF_PREFIX = 'lieu_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 30; $i++) {
            $lieu = new Lieu();
            if (method_exists($lieu, 'setNom'))       $lieu->setNom($faker->company);
            if (method_exists($lieu, 'setRue'))       $lieu->setRue($faker->streetAddress);
            if (method_exists($lieu, 'setLatitude'))  $lieu->setLatitude($faker->latitude);
            if (method_exists($lieu, 'setLongitude')) $lieu->setLongitude($faker->longitude);
            /** @var \App\Entity\Ville $ville */
            $ville = $this->getReference(
                VilleFixtures::VILLE_REF_PREFIX.$faker->numberBetween(0, 11),
                \App\Entity\Ville::class
            );
            $lieu->setVille($ville);
            if (method_exists($lieu, 'setVille')) {
                $lieu->setVille($ville);
            }

            $manager->persist($lieu);
            $this->addReference(self::LIEU_REF_PREFIX.$i, $lieu);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VilleFixtures::class];
    }
}
