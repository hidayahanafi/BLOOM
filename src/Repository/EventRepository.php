<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    //    /**
    //     * @return Event[] Returns an array of Event objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findAllSorted(string $field, string $order = 'ASC')
    {
        if ($field === 'prix') {
            $sql = "SELECT e.* FROM event e ORDER BY CAST(e.prix AS DECIMAL(10,2)) $order";
            return $this->entityManager->getConnection()->fetchAllAssociative($sql);
        }

        return $this->createQueryBuilder('e')
            ->orderBy('e.' . $field, $order)
            ->getQuery()
            ->getResult();
    }
    public function searchByCriteria(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.titre LIKE :query') // Recherche par titre
            ->orWhere('e.lieu LIKE :query') // Recherche par lieu
            ->orWhere('e.date LIKE :query') // Recherche par date
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
    public function countParticipantsByEvent()
    {
        return $this->createQueryBuilder('e')
            ->select('e.titre as name, COUNT(u.id) as count')
            ->leftJoin('e.users', 'u')
            ->groupBy('e.id')
            ->getQuery()
            ->getResult();
    }





}
