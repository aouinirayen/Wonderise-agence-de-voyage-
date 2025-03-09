<?php

namespace App\Controller;

use App\Form\PhoneNumberType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PhoneController extends AbstractController
{
    #[Route('/profile/phone', name: 'update_phone')]
    #[IsGranted('ROLE_USER')]
    public function updatePhone(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(PhoneNumberType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le numéro de téléphone est automatiquement mis à jour dans l'entité user
            $entityManager->flush();

            $this->addFlash('success', 'Your phone number has been updated successfully.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/update_phone.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
