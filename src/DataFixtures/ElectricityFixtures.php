<?php

namespace App\DataFixtures;

use App\Entity\ElectricityProvider;
use App\Entity\ElectricityTariff;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ElectricityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $provider = new ElectricityProvider('SmartPlan Botswana');
        $manager->persist($provider);

        $tariff = new ElectricityTariff('1.3200', $provider, 'default');
        $manager->persist($tariff);

        $manager->flush();
    }
}
