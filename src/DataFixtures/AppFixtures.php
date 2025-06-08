<?php

namespace App\DataFixtures;

use App\Factory\AdviceFactory;
use App\Factory\MonthFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        MonthFactory::createAllMonths(12);
        UserFactory::createMany(5);
        AdviceFactory::createMany(5);
        $manager->flush();
    }
}
