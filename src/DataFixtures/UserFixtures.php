<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_REF_PREFIX = 'user_';

    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Admin de démo
        $admin = new User();
        if (method_exists($admin, 'setEmail'))    $admin->setEmail('admin@example.test');
        if (method_exists($admin, 'setUsername')) $admin->setUsername('admin');
        if (method_exists($admin, 'setRoles'))    $admin->setRoles(['ROLE_ADMIN']);
        if (method_exists($admin, 'setNom'))      $admin->setNom('Admin');
        if (method_exists($admin, 'setPrenom'))   $admin->setPrenom('Demo');
        if (method_exists($admin, 'setTelephone'))$admin->setTelephone('0600000000');
        if (method_exists($admin, 'setActif'))    $admin->setActif(true);
        if (method_exists($admin, 'setSite'))     $admin->setSite($this->getReference(SiteFixtures::SITE_REF_PREFIX.'0', \App\Entity\Site::class));
        if (method_exists($admin, 'setPassword')) $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);
        $this->addReference(self::USER_REF_PREFIX.'admin', $admin);

        // Utilisateurs
        for ($i = 0; $i < 25; $i++) {
            $user = new User();
            if (method_exists($user, 'setEmail'))     $user->setEmail($faker->unique()->safeEmail());
            if (method_exists($user, 'setUsername'))  $user->setUsername($faker->unique()->userName());
            if (method_exists($user, 'setRoles'))     $user->setRoles(['ROLE_USER']);
            if (method_exists($user, 'setNom'))       $user->setNom($faker->lastName());
            if (method_exists($user, 'setPrenom'))    $user->setPrenom($faker->firstName());
            if (method_exists($user, 'setTelephone')) $user->setTelephone($faker->phoneNumber());
            if (method_exists($user, 'setActif'))     $user->setActif($faker->boolean(90));
            if (method_exists($user, 'setSite')) {
                $user->setSite($this->getReference(
                    SiteFixtures::SITE_REF_PREFIX.$faker->numberBetween(0, 5),
                    \App\Entity\Site::class
                ));            }
            if (method_exists($user, 'setPassword')) {
                $user->setPassword($this->hasher->hashPassword($user, 'password'));
            }

            $manager->persist($user);
            $this->addReference(self::USER_REF_PREFIX.$i, $user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SiteFixtures::class];
    }
}
