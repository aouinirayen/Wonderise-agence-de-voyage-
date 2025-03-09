<?php

namespace App\Controller;

use App\Entity\Guide;
use App\Form\GuideType;
use App\Repository\GuideRepository;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/guide')]
final class GuideController extends AbstractController {
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
////////////
#[Route('/second', name: 'app_guide_index1', methods: ['GET'])]
public function index1(GuideRepository $guideRepository, EntityManagerInterface $entityManager): Response
{
    $guides = $guideRepository->findAll();
        
    // Initialiser les statistiques si nécessaire
    foreach ($guides as $guide) {
        if ($guide->getNombreAvis() === null) {
            $guide->setNombreAvis(0);
            $entityManager->persist($guide);
        }
    }
    $entityManager->flush();

    return $this->render('guide/boff/index.html.twig', [
        'guides' => $guides,
    ]);
}

#[Route('add/new', name: 'app_guide_new1', methods: ['GET', 'POST'])]
public function new1(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $guide = new Guide();
    $form = $this->createForm(GuideType::class, $guide);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $photoFile = $form->get('photo')->getData();

        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
            $uploadDir = $this->getParameter('photos_directory');

            try {
                // Créer le dossier s'il n'existe pas
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $photoFile->move(
                    $uploadDir,
                    $newFilename
                );
                $guide->setPhoto($newFilename);
                $this->logger->info('Photo uploaded successfully');
            } catch (FileException $e) {
                $this->logger->error('Upload error:', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
            }
        } else {
            // Si aucune photo n'est fournie, utiliser une photo par défaut
            $guide->setPhoto('default-profile.jpg');
        }

        $entityManager->persist($guide);
        $entityManager->flush();
        return $this->redirectToRoute('app_guide_index1');
    }

    return $this->render('guide/boff/new.html.twig', [
        'form' => $form,
    ]);
}

#[Route('/gfgf/{id}', name: 'app_guide_show1', methods: ['GET'])]
public function show1(Guide $guide, EvenementRepository $evenementRepository): Response
{
    // Récupérer les événements associés au guide
    $evenements = $evenementRepository->findBy(['guide' => $guide]);

    return $this->render('guide/boff/show.html.twig', [
        'guide' => $guide,
        'evenements' => $evenements
    ]);
}

#[Route('/{id}/edit', name: 'app_guide_edit1', methods: ['GET', 'POST'])]
public function edit1(Request $request, Guide $guide, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $form = $this->createForm(GuideType::class, $guide);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $photoFile = $form->get('photo')->getData();
        $this->logger->info('Photo file in edit:', ['file' => $photoFile ? 'yes' : 'no']);

        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
            $uploadDir = $this->getParameter('photos_directory');

            try {
                // Créer le dossier s'il n'existe pas
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $photoFile->move(
                    $uploadDir,
                    $newFilename
                );
                $guide->setPhoto($newFilename);
                $this->logger->info('Photo updated successfully');
            } catch (FileException $e) {
                $this->logger->error('Upload error in edit:', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
            }
        }

        try {
            $entityManager->flush();
            $this->logger->info('Guide updated successfully');
            return $this->redirectToRoute('app_guide_index1', [], Response::HTTP_SEE_OTHER);
        } catch (\Exception $e) {
            $this->logger->error('Database error in edit:', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Une erreur est survenue lors de la sauvegarde: ' . $e->getMessage());
        }
    } else {
        $errors = $form->getErrors(true);
        $this->logger->error('Validation errors in edit:', ['errors' => $errors]);
    }

    return $this->render('guide/boff/edit.html.twig', [
        'guide' => $guide,
        'form' => $form->createView(),
        'errors' => $errors,
    ]);
}

#[Route('/{id}', name: 'app_guide_delete1', methods: ['POST'])]
public function delete1(Request $request, Guide $guide, EntityManagerInterface $entityManager, EvenementRepository $evenementRepository): Response
{
    if ($this->isCsrfTokenValid('delete'.$guide->getId(), $request->getPayload()->getString('_token'))) {
        try {
            // Récupérer tous les événements associés au guide
            $evenements = $evenementRepository->findBy(['guide' => $guide]);
            
            // Supprimer d'abord tous les événements associés
            foreach ($evenements as $evenement) {
                $entityManager->remove($evenement);
            }
            
            // Puis supprimer le guide
            $entityManager->remove($guide);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le guide et ses événements associés ont été supprimés avec succès.');
        } catch (\Exception $e) {
            $this->logger->error('Error deleting guide:', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du guide.');
        }
    }

    return $this->redirectToRoute('app_guide_index1', [], Response::HTTP_SEE_OTHER);
}
///////////

    #[Route('/guide/search', name: 'guide_search')]
    public function search(Request $request, GuideRepository $guideRepository): JsonResponse
    {
        try {
            $searchTerm = $request->query->get('q');
            $guides = $guideRepository->searchGuides($searchTerm);
            
            return $this->json(
                array_map(
                    fn($guide) => [
                        'id' => $guide->getId(),
                        'nom' => $guide->getNom(),
                        'prenom' => $guide->getPrenom(),
                        'email' => $guide->getEmail(),
                        'numTelephone' => $guide->getNumTelephone(),
                        'description' => $guide->getDescription(),
                        'photo' => $guide->getPhoto(),
                        'facebook' => $guide->getFacebook(),
                        'instagram' => $guide->getInstagram(),
                    ],
                    $guides
                )
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route(name: 'app_guide_index', methods: ['GET'])]
    public function index(GuideRepository $guideRepository): Response
    {
        return $this->render('guide/index.html.twig', [
            'guides' => $guideRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_guide_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $guide = new Guide();
        $form = $this->createForm(GuideType::class, $guide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
                $uploadDir = $this->getParameter('photos_directory');

                try {
                    // Créer le dossier s'il n'existe pas
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $photoFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    $guide->setPhoto($newFilename);
                    $this->logger->info('Photo uploaded successfully');
                } catch (FileException $e) {
                    $this->logger->error('Upload error:', ['error' => $e->getMessage()]);
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
                }
            } else {
                // Si aucune photo n'est fournie, utiliser une photo par défaut
                $guide->setPhoto('default-profile.jpg');
            }

            $entityManager->persist($guide);
            $entityManager->flush();
            return $this->redirectToRoute('app_guide_index');
        }

        return $this->render('guide/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_show', methods: ['GET'])]
    public function show(Guide $guide, EvenementRepository $evenementRepository): Response
    {
        // Récupérer les événements associés au guide
        $evenements = $evenementRepository->findBy(['guide' => $guide]);

        return $this->render('guide/show.html.twig', [
            'guide' => $guide,
            'evenements' => $evenements
        ]);
    }

    #[Route('/{id}/edit', name: 'app_guide_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Guide $guide, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(GuideType::class, $guide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            $this->logger->info('Photo file in edit:', ['file' => $photoFile ? 'yes' : 'no']);

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
                $uploadDir = $this->getParameter('photos_directory');

                try {
                    // Créer le dossier s'il n'existe pas
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $photoFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    $guide->setPhoto($newFilename);
                    $this->logger->info('Photo updated successfully');
                } catch (FileException $e) {
                    $this->logger->error('Upload error in edit:', ['error' => $e->getMessage()]);
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
                }
            }

            try {
                $entityManager->flush();
                $this->logger->info('Guide updated successfully');
                return $this->redirectToRoute('app_guide_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->logger->error('Database error in edit:', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Une erreur est survenue lors de la sauvegarde: ' . $e->getMessage());
            }
        } else {
            $errors = $form->getErrors(true);
            $this->logger->error('Validation errors in edit:', ['errors' => $errors]);
        }

        return $this->render('guide/edit.html.twig', [
            'guide' => $guide,
            'form' => $form->createView(),
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_delete', methods: ['POST'])]
    public function delete(Request $request, Guide $guide, EntityManagerInterface $entityManager, EvenementRepository $evenementRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$guide->getId(), $request->getPayload()->getString('_token'))) {
            try {
                // Récupérer tous les événements associés au guide
                $evenements = $evenementRepository->findBy(['guide' => $guide]);
                
                // Supprimer d'abord tous les événements associés
                foreach ($evenements as $evenement) {
                    $entityManager->remove($evenement);
                }
                
                // Puis supprimer le guide
                $entityManager->remove($guide);
                $entityManager->flush();
                
                $this->addFlash('success', 'Le guide et ses événements associés ont été supprimés avec succès.');
            } catch (\Exception $e) {
                $this->logger->error('Error deleting guide:', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du guide.');
            }
        }

        return $this->redirectToRoute('app_guide_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/like', name: 'app_guide_like')]
    public function like(Guide $guide, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentLikes = $guide->getNombreAvis();
        $newLikes = $currentLikes > 0 ? 0 : 1;
        $guide->setNombreAvis($newLikes);
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'likes' => $newLikes,
            'isLiked' => $newLikes > 0
        ]);
    }

    #[Route('/{id}/rate', name: 'app_guide_rate', methods: ['POST'])]
    public function rate(Request $request, Guide $guide, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('rate-guide', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $rating = $data['rating'] ?? null;

            if ($rating === null) {
                throw new \InvalidArgumentException('La note est requise');
            }

            // Incrémenter le nombre d'avis
            $guide->incrementNombreAvis();
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Avis enregistré avec succès',
                'nombreAvis' => $guide->getNombreAvis()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement de l\'avis', [
                'error' => $e->getMessage(),
                'guide_id' => $guide->getId()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement de l\'avis'
            ], 500);
        }
    }
}
