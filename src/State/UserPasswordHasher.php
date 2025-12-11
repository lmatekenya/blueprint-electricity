<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserPasswordHasher implements ProcessorInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof \App\Entity\User) {
            return $data;
        }

        // Hash password
        $hashed = $this->passwordHasher->hashPassword($data, $data->getPassword());
        $data->setPassword($hashed);

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
