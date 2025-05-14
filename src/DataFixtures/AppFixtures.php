<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Advice;

class AppFixtures extends Fixture
{
    private readonly UserPasswordHasherInterface $hasher;
    private $faker;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
        $this->faker = Factory::create('fr_FR');
    }
    
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setRoles(['ROLE_USER']);
            $user->setUsername($this->faker->userName());
            $user->setPostcode($this->faker->postcode());
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
        }

        for ($i = 0; $i < 10; $i++) {
            $advice = new Advice();
            $advice->setMonth($this->faker->numberBetween(1, 12));
            $advice->setDescription($this->faker->sentence());
            $manager->persist($advice);
        }

        $manager->flush();
    }
}
