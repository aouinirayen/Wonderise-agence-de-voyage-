<?php

namespace App\Repository;

use App\Entity\Guide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guide>
 */
class GuideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guide::class);
    }

//    /**
//     * @return Guide[] Returns an array of Guide objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Guide
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function searchGuides(?string $searchTerm)
    {
        if (!$searchTerm) {
            return [];
        }

        $qb = $this->createQueryBuilder('g');
        $searchTerm = '%' . $searchTerm . '%';

        return $qb
            ->where('g.nom LIKE :searchTerm')
            ->orWhere('g.prenom LIKE :searchTerm')
            ->orWhere('g.email LIKE :searchTerm')
            ->orWhere('g.numTelephone LIKE :searchTerm')
            ->orWhere('g.description LIKE :searchTerm')
            ->setParameter('searchTerm', $searchTerm)
            ->orderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
