<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\ElectricityTransaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setName('Admin User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Create regular user
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setName('Regular User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $manager->persist($user);

        // Create sample transactions
        for ($i = 1; $i <= 20; $i++) {
            $transaction = new ElectricityTransaction();
            $transaction->setTransID('TXN' . str_pad($i, 9, '0', STR_PAD_LEFT));
            $transaction->setMeterNumber('123456789' . str_pad($i, 2, '0', STR_PAD_LEFT));
            $transaction->setAmount((string)(rand(10, 500) + rand(0, 99) / 100));
            $transaction->setStatus($i % 4 === 0 ? 'failed' : 'success');
            $transaction->setProvider($i % 3 === 0 ? 'craftapi' : 'prepaidplus');
            $transaction->setUser($user);
            $transaction->setReceiptNo('REC' . str_pad($i, 6, '0', STR_PAD_LEFT));
            $transaction->setUnits(rand(50, 500));
            $transaction->setToken(bin2hex(random_bytes(32)));
            $transaction->setDetails([
                'customer_name' => 'Customer ' . $i,
                'tariff' => 'Residential',
                'units' => $transaction->getUnits(),
                'amount' => $transaction->getAmount()
            ]);

            $manager->persist($transaction);
        }

        $manager->flush();
    }
}
