<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use DateTimeImmutable;
use App\Entity\Enum\Etat;
use Faker\Generator;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $users = $manager->getRepository(User::class)->findAll();
        $lieux = $manager->getRepository(Lieu::class)->findAll();
        $sites = $manager->getRepository(Site::class)->findAll();

        for ($i = 0; $i < 40; $i++) {
            $sortie = new Sortie();
            $sortie->setNom(ucfirst($faker->words(mt_rand(2,4), true)));
            $sortie->setInfosSortie($faker->realText(120));
            $sortie->setNbInscriptionMax($faker->numberBetween(6, 30));
            $sortie->setDuree($faker->numberBetween(30, 240));
            $startMutable = $faker->dateTimeBetween('-20 days', '+40 days');
            $sortie->setDateHeureDebut(DateTimeImmutable::createFromMutable($startMutable));
            $sortie->setDateLimiteInscription(DateTimeImmutable::createFromMutable($startMutable->modify('-'.rand(2,10).' days')));
            $sortie->setLieu($faker->randomElement($lieux));
            $sortie->setSite($faker->randomElement($sites));
            $sortie->setOrganisateur($faker->randomElement($users));
            $this->setEtat($sortie, $faker);
            $this->addParticipants($sortie, $faker, $users);

            $manager->persist($sortie);
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
        ];
    }

    /**
     * Helper qui permet de mettre l'État d'une sortie à une valeur cohérente.
     * @param Sortie $sortie
     * @param Generator $faker
     * @return void
     */
    private function setEtat(Sortie $sortie, Generator $faker): void
    {
        $now = new DateTimeImmutable();
        $duree = $sortie->getDuree();
        $dateDebut = \DateTime::createFromImmutable($sortie->getDateHeureDebut());
        $dateFin = (clone $dateDebut)->modify("+ $duree minutes");

        // 15 % de chance que la sortie soit supprimer / clôturer
        if ($faker->boolean(10)) {
            $sortie->setEtat(Etat::Cloturee);
        }
        // 15 % de chance qu'elle soit annulée
        else if ($faker->boolean(5)) {
            $sortie->setEtat(Etat::Annulee);
        }
        else {
            // En met en passée si la date est dépassée
            if ($dateFin <= $now) {
                $sortie->setEtat(Etat::Passee);
            }
            // 50 % de mettre en ouvrir ou en Création quand la date supérieur à celle d'ouverture
            else if ($dateDebut > $now) {
                if ($faker->boolean(50)) {
                    $sortie->setEtat(Etat::Creee);
                } else {
                    $sortie->setEtat(Etat::Ouverte);
                }
            }
            // Quand date est après date début et avant date de fin alors Activité En Cours
            else {
                $sortie->setEtat(Etat::Activite);
            }
        }
    }

    /**
     * Helper qui permet d'ajouter des participants aux sorties
     * @param Sortie $sortie
     * @param Generator $faker
     * @param User[] $users
     * @return void
     */
    private function addParticipants(Sortie $sortie, Generator $faker, array $users): void
    {
        if (in_array($sortie->getEtat(), [Etat::Cloturee, Etat::Creee])) {
            return;
        }

        $nbParticipants = $faker->numberBetween(0, 5);

        for ($i = 0; $i < $nbParticipants; $i++) {
            do {
                $user = $faker->randomElement($users);
            } while ($user === $sortie->getOrganisateur());
            $sortie->addParticipant($user);
        }
    }
}
