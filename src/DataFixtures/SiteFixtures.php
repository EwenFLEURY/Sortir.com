<?php

namespace App\DataFixtures;

use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SiteFixtures extends Fixture implements DependentFixtureInterface
{
    public const SITE_REF_PREFIX = 'site_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 6; $i++) {
            $site = new Site();
            if (method_exists($site, 'setNom')) {
                $site->setNom($faker->unique()->company);
            }
            if (method_exists($site, 'setVille')) {
                /** @var \App\Entity\Ville $ville */
                $ville = $this->getReference(
                    VilleFixtures::VILLE_REF_PREFIX.$faker->numberBetween(0, 11),
                    \App\Entity\Ville::class
                );
                $site->setVille($ville);
                $site->setVille($ville);
            }

            $manager->persist($site);
            $this->addReference(self::SITE_REF_PREFIX.$i, $site);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VilleFixtures::class];
    }
}
