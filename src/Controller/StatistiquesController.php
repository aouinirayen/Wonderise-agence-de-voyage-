<?php

namespace App\Controller;

use App\Service\StatistiquesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class StatistiquesController extends AbstractController
{
    private $statistiquesService;

    public function __construct(StatistiquesService $statistiquesService)
    {
        $this->statistiquesService = $statistiquesService;
    }

    #[Route('/statistiques', name: 'admin_statistiques')]
    public function index(): Response
    {
        // Récupérer les données des réclamations les plus fréquentes
        $reclamationsFrequentes = $this->statistiquesService->getReclamationsFrequentes(10);
        
        return $this->render('back_office/statistiques/index.html.twig', [
            'reclamations_frequentes' => $reclamationsFrequentes
        ]);
    }
    
    #[Route('/api/statistiques/reclamations-frequentes', name: 'api_stats_reclamations_frequentes')]
    public function getReclamationsFrequentes(): JsonResponse
    {
        $reclamationsFrequentes = $this->statistiquesService->getReclamationsFrequentes(10);
        return new JsonResponse($reclamationsFrequentes);
    }
}
