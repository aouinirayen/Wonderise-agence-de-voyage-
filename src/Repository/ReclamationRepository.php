<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    /**
     * Récupère toutes les réclamations avec leurs réponses associées.
     *
     * @return Reclamation[]
     */
    public function findAllWithResponses(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.reponses', 'rep')  // Jointure avec les réponses
            ->addSelect('rep')               // Sélectionner aussi les réponses
            ->getQuery()
            ->getResult();
    }

    
    public function searchReclamations(?string $keyword, ?string $status, string $sort = 'r.Date', string $order = 'DESC')
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.reponses', 'rep')
            ->addSelect('rep');
    
        // 🔍 Recherche par mot-clé (Objet ou Description)
        if (!empty($keyword)) {
            $qb->andWhere('r.Objet LIKE :keyword OR r.Description LIKE :keyword')
               ->setParameter('keyword', "%$keyword%");
        }
    
        // 🎯 Filtrage par statut
        if (!empty($status)) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }
    
        // 🔥 Sécuriser `sort` pour éviter les injections SQL
        $allowedSorts = ['r.Objet', 'r.Date', 'r.status'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'r.Date'; // Par défaut, tri par date
        }
    
        // 🔥 Sécuriser `order` pour éviter les erreurs SQL
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }
    
        // 📝 Appliquer le tri (en s'assurant que les valeurs sont sûres)
        $qb->orderBy($sort, $order);
    
        return $qb->getQuery(); // ✅ Retourne une Query pour KnpPaginator
    }
}    