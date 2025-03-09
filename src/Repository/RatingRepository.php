<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function countLikes(Country $country): int
    {
        return $this->count([
            'country' => $country,
            'isLike' => true,
        ]);
    }

    public function countDislikes(Country $country): int
    {
        return $this->count([
            'country' => $country,
            'isLike' => false,
        ]);
    }
}
