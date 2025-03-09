<?php

namespace App\Service;

use App\Entity\Reclamation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StatistiquesService
{
    private $entityManager;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Récupère les types de réclamations les plus fréquents
     * 
     * @param int $limit Nombre maximum de résultats à retourner
     * @return array
     */
    public function getReclamationsFrequentes(int $limit = 10): array
    {
        $this->logger->info('Récupération des réclamations les plus fréquentes');
        
        try {
            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder
                ->select('r.Objet, COUNT(r.id) as count')
                ->from(Reclamation::class, 'r')
                ->groupBy('r.Objet')
                ->orderBy('count', 'DESC')
                ->setMaxResults($limit);
            
            $result = $queryBuilder->getQuery()->getResult();
            
            $this->logger->info(sprintf('Récupération de %d types de réclamations fréquentes', count($result)));
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des réclamations fréquentes: ' . $e->getMessage());
            return [];
        }
    }
}
