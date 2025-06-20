<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findByPlanningIds(array $planningIds): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.planning', 'p')
            ->where('p.id IN (:planningIds)')
            ->setParameter('planningIds', $planningIds)
            ->orderBy('a.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findByDoctor(int $doctorId): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.planning', 'p')
            ->where('p.doctor = :doctorId')
            ->setParameter('doctorId', $doctorId)
            ->orderBy('a.startAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find appointments that conflict with the given time slot
     */
    public function findConflictingAppointments(
        int $doctorId,
        \DateTime $startDateTime,
        \DateTime $endDateTime,
        ?int $excludeAppointmentId = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->join('a.planning', 'p')
            ->where('p.doctor = :doctorId')
            ->andWhere('a.startAt BETWEEN :startDateTime AND :beforeEndDateTime')
            ->setParameter('doctorId', $doctorId)
            ->setParameter('startDateTime', $startDateTime)
            ->setParameter('beforeEndDateTime', (clone $endDateTime)->modify('-1 second'));

        if ($excludeAppointmentId) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeAppointmentId);
        }

        return $qb->getQuery()->getResult();
    }
    //    /**
    //     * @return Appointment[] Returns an array of Appointment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Appointment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}