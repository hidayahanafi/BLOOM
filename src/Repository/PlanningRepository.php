<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Planning>
 */
class PlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planning::class);
    }

    public function findAvailableSlots(\DateTimeInterface $date): array
    {
        $planning = $this->findOneBy(['date' => $date]);
        if (!$planning) {
            return [];
        }
        return $planning->getAvailableTimes();
    }
    /**
     * Fetch all planning events for the calendar.
     *
     * @return Planning[] Returns an array of Planning objects
     */
    public function findAllPlanningEvents(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'u')  // Select Planning and associated doctor (User)
            ->leftJoin('p.doctor', 'u')  // Join the doctor (User) table
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Planning[] Returns an array of Planning objects
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

    //    public function findOneBySomeField($value): ?Planning
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}