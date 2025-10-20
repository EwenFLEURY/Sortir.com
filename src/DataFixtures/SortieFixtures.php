<?php

namespace App\DataFixtures;

use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use DateTimeImmutable;
use App\Entity\Enum\Etat;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public const SORTIE_REF_PREFIX = 'sortie_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 40; $i++) {
            $sortie = new Sortie();

            if (method_exists($sortie, 'setNom'))                 $sortie->setNom(ucfirst($faker->words(mt_rand(2,4), true)));
            if (method_exists($sortie, 'setInfosSortie'))        $sortie->setInfosSortie($faker->realText(120));
            $nbMax = random_int(6, 30);
            if (method_exists($sortie, 'setNbInscriptionMax')) {
                $sortie->setNbInscriptionMax($nbMax);
            } elseif (method_exists($sortie, 'setNbInscriptionsMax')) {
                $sortie->setNbInscriptionsMax($nbMax);
            } else {
                throw new \RuntimeException('Setter nb_inscription_max introuvable sur App\Entity\Sortie');
            }

            if (method_exists($sortie, 'setDuree'))              $sortie->setDuree($faker->numberBetween(30, 240));


            $startMutable = $faker->dateTimeBetween('-20 days', '+40 days');
            $start = \DateTimeImmutable::createFromMutable($startMutable);
            $limitMutable = (clone $startMutable)->modify('-'.mt_rand(2,10).' days');
            $limit = \DateTimeImmutable::createFromMutable($limitMutable);
            if (method_exists($sortie, 'setDateHeureDebut'))         $sortie->setDateHeureDebut($start);
            if (method_exists($sortie, 'setDateLimiteInscription'))  $sortie->setDateLimiteInscription($limit);

            // Relations
            if (method_exists($sortie, 'setLieu')) {
                $sortie->setLieu($this->getReference(
                    LieuFixtures::LIEU_REF_PREFIX.$faker->numberBetween(0, 29),
                    \App\Entity\Lieu::class
                ));
            }
            if (method_exists($sortie, 'setSite')) {
                $sortie->setSite($this->getReference(
                    SiteFixtures::SITE_REF_PREFIX.$faker->numberBetween(0, 5),
                    \App\Entity\Site::class
                ));
            }
            if (method_exists($sortie, 'setOrganisateur')) {
                $sortie->setOrganisateur($this->getReference(
                    UserFixtures::USER_REF_PREFIX.$faker->numberBetween(0, 24),
                    \App\Entity\User::class
                ));
            }
            $sortie->setEtat($faker->randomElement([
                Etat::Creee,
                Etat::Ouverte,
                Etat::Cloturee,
                Etat::Activite,
                Etat::Passee,
                Etat::Annulee,
            ]));

            $manager->persist($sortie);
            $this->addReference(self::SORTIE_REF_PREFIX.$i, $sortie);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VilleFixtures::class,
            LieuFixtures::class,
            SiteFixtures::class,
            UserFixtures::class,
            EtatFixtures::class,
        ];
    }
}
