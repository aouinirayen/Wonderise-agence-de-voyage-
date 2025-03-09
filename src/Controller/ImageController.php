<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/images/logo', name: 'app_image_logo')]
    public function logo(): Response
    {
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/wonderwiselogo.png';
        
        return new Response(
            file_get_contents($logoPath),
            200,
            ['Content-Type' => 'image/png']
        );
    }
}
