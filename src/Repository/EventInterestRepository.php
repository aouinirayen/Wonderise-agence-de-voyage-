<?php

namespace App\Repository;

use App\Entity\EventInterest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventInterestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventInterest::class);
    }

    public function isInterestedInEvent(int $eventId): bool
    {
        $result = $this->createQueryBuilder('ei')
            ->andWhere('ei.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}
