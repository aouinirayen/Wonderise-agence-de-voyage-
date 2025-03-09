<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontOfficeController extends AbstractController{
    #[Route('/', name: 'app_front_office')]
    public function index(): Response
    {
        return $this->render('front_office/index.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }

    #[Route('/about', name: 'app_front_office_about')]
    public function about(): Response
    {
        return $this->render('front_office/about.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }

    #[Route('/contact', name: 'app_front_office_contact')]
    public function contact(): Response
    {
        return $this->render('front_office/contact.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }

    #[Route('/services', name: 'app_front_office_services')]
    public function services(): Response
    {
        return $this->render('front_office/services.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }

    #[Route('/packages', name: 'app_front_office_packages')]
    public function packages(): Response
    {
        return $this->render('front_office/packages.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }

    #[Route('/blog', name: 'app_front_office_blog')]
    public function blog(): Response
    {
        return $this->render('front_office/blog.html.twig', [
            'controller_name' => 'FrontOfficeController',
        ]);
    }
    
    
}
