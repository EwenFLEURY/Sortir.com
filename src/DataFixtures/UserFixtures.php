<?php

namespace App\DataFixtures;

use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $sites = $manager->getRepository(Site::class)->findAll();

        // Admin de démo
        $admin = new User();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setNom('Admin');
        $admin->setPrenom('Demo');
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.test');
        $admin->setTelephone('06 00 00 00 00');
        $admin->setSite($sites[0]);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));

        $manager->persist($admin);

        // Utilisateurs
        for ($i = 0; $i < 25; $i++) {
            $user = new User();
            $user->setRoles(['ROLE_USER']);
            $user->setNom($faker->unique()->firstName);
            $user->setPrenom($faker->unique()->lastName);
            $user->setUsername($this->generateUsername($user));
            $user->setEmail(sprintf("%s.%s@exmaple.test", $user->getPrenom(), $user->getNom()));
            $user->setTelephone($faker->phoneNumber);
            $user->setActif($faker->boolean(90));
            $user->setSite($faker->randomElement($sites));
            $user->setPassword($this->hasher->hashPassword($user, 'password'));

            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SiteFixtures::class
        ];
    }

    /**
     * Helper qui permet de créer un username en fonction du nom/prénom
     * @param User $user
     * @return string
     */
    private function generateUsername(User $user): string
    {
        $choix = rand(0, 2);
        if ($choix === 0) {
            return sprintf("%s%s", $user->getPrenom()[0], $user->getNom());
        } else if($choix === 1) {
            return sprintf("%s.%s", $user->getPrenom(), $user->getNom());
        } else {
          return sprintf("%s%d", $user->getPrenom(), rand(10, 99));
        }
    }
}
