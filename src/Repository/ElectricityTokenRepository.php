<?php
namespace App\Repository;

use App\Entity\ElectricityToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ElectricityTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElectricityToken::class);
    }

    public function findUnusedByHash(string $hash): ?ElectricityToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tokenHash = :hash')
            ->andWhere('t.used = false')
            ->setParameter('hash', $hash)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
