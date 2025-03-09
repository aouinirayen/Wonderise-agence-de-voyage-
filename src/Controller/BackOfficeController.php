<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
final class BackOfficeController extends AbstractController{

    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
    #[Route('/clients', name: 'admin_client_list')]
    public function listClients(EntityManagerInterface $entityManager): Response
    {
        $qb = $entityManager->getRepository(User::class)->createQueryBuilder('u');
        $clients = $qb->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('u.roles NOT LIKE :admin_role')
            ->setParameter('admin_role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();
        
        return $this->render('back_office/list_client.html.twig', [
            'clients' => $clients
        ]);
    }

    #[Route('/clients/{id}/block', name: 'admin_client_block', methods: ['POST'])]
    public function blockClient(User $client, EntityManagerInterface $entityManager): JsonResponse
    {
        $client->setIsBlocked(true);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/clients/{id}/unblock', name: 'admin_client_unblock', methods: ['POST'])]
    public function unblockClient(User $client, EntityManagerInterface $entityManager): JsonResponse
    {
        $client->setIsBlocked(false);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/clients/{id}/delete', name: 'admin_client_delete', methods: ['POST'])]
    public function deleteClient(Request $request, User $client, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('admin_client_list');
        }
        $entityManager->remove($client);
        $entityManager->flush();
        $this->addFlash('success', 'Client deleted successfully');
        return $this->redirectToRoute('admin_client_list');
    }
    #[Route('/dashboard', name: 'app_back_office')]
    #[Route('/', name: 'admin_index')]
    public function adminIndex(): Response
    {
        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }

    #[Route('/profile', name: 'admin_profile', methods: ['GET', 'POST'])]
    public function viewProfile(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $user = $this->getUser();
            $user->setFullName($request->request->get('fullName'));
            $user->setEmail($request->request->get('email'));
            $user->setPhone($request->request->get('phone'));

            $profilePhotoFile = $request->files->get('profilePhoto');
            if ($profilePhotoFile) {
                $originalFilename = pathinfo($profilePhotoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePhotoFile->guessExtension();

                try {
                    $profilePhotoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                    $user->setProfilePhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading profile photo');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully');

            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('back_office/profile.html.twig', [
            'admin' => $this->getUser()
        ]);
    }

    #[Route('/profile/edit', name: 'admin_profile_edit', methods: ['GET', 'POST'])]    
    public function editProfile(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $user = $this->getUser();
            $user->setFullName($request->request->get('fullName'));
            $user->setEmail($request->request->get('email'));
            $user->setPhone($request->request->get('phone'));

            $profilePhotoFile = $request->files->get('profilePhoto');
            if ($profilePhotoFile) {
                $originalFilename = pathinfo($profilePhotoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePhotoFile->guessExtension();

                try {
                    $profilePhotoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                    $user->setProfilePhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading profile photo');
                }
            }

            if ($request->request->get('newPassword')) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword(
                        $user,
                        $request->request->get('newPassword')
                    )
                );
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully');

            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('back_office/edit_profile.html.twig', [
            'admin' => $this->getUser()
        ]);
    }
}
