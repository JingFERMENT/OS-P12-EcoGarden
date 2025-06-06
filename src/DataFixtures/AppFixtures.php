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
        AdviceFactory::createMany(5);
        UserFactory::createMany(5);
        MonthFactory::createAllMonths(12);
        $manager->flush();
    }
}
