<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function searchReservations(?string $search): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->leftJoin('r.offre', 'o');

        if ($search) {
            $qb->andWhere('c.nom LIKE :search OR c.email LIKE :search OR o.titre LIKE :search OR r.id = :id')
                ->setParameter('search', '%' . $search . '%')
                ->setParameter('id', is_numeric($search) ? $search : -1);
        }

        return $qb->getQuery()->getResult();
    }
}
