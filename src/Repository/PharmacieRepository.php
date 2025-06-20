<?php

namespace App\Repository;

use App\Entity\Pharmacie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Elastica\Query;
use Elastica\Query\MatchQuery;
/**
 * @extends ServiceEntityRepository<Pharmacie>
 */
class PharmacieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pharmacie::class);
    }

    //    /**
//     * @return Pharmacie[] Returns an array of Pharmacie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?Pharmacie
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function countByType(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.type as name, COUNT(p.id) as count')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();
    }

    public function countByVille(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.ville as name, COUNT(p.id) as count')
            ->groupBy('p.ville')
            ->getQuery()
            ->getResult();
    }


}
