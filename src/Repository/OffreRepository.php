<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

//    /**
//     * @return Offre[] Returns an array of Offre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Offre
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function searchOffres(?string $search = null, ?float $maxPrice = null, ?int $minPlaces = null): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($search) {
            $qb->andWhere('o.titre LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($maxPrice) {
            $qb->andWhere('o.prix <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        if ($minPlaces) {
            $qb->andWhere('o.placesDisponibles >= :minPlaces')
               ->setParameter('minPlaces', $minPlaces);
        }

        return $qb->getQuery()->getResult();
    }
}
