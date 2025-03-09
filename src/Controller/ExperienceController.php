<?php

namespace App\Controller;

use App\Entity\Experience;
use App\Entity\Commentaire;
use App\Form\ExperienceType;
use App\Form\CommentaireType;
use App\Repository\ExperienceRepository;
use App\Repository\CommentaireRepository;
use App\Service\AirQualityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/experience')]
class ExperienceController extends AbstractController
{
    private $airQualityService;

    public function __construct(AirQualityService $airQualityService)
    {
        $this->airQualityService = $airQualityService;
    }

    #[Route('/', name: 'app_experience_index', methods: ['GET'])]
    public function index(Request $request, ExperienceRepository $experienceRepository): Response
    {
        $searchTerm = $request->query->get('search', '');
        
        $experiences = $experienceRepository->searchByLieu($searchTerm);
        
        return $this->render('experience/index.html.twig', [
            'experiences' => $experiences,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/new', name: 'app_experience_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $experience = new Experience();
        $form = $this->createForm(ExperienceType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Set creation date
                    $experience->setDateCreation(new \DateTime());

                    // Get the location from the form
                    $location = $experience->getLieu();
                    
                    // Fetch air quality data if location is provided
                    if ($location) {
                        try {
                            $airQualityData = $this->airQualityService->getAirQuality($location);
                            $experience->setAirQualityData($airQualityData);
                        } catch (\Exception $e) {
                            // Log the error but continue with the experience creation
                            $this->addFlash('warning', 'Could not fetch air quality data, but your experience will be saved.');
                        }
                    }

                    $entityManager->persist($experience);
                    $entityManager->flush();

                    $this->addFlash('success', 'Experience created successfully!');
                    return $this->redirectToRoute('app_experience_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred while saving the experience. Please try again.');
                }
            } else {
                $this->addFlash('error', 'Please check the form for errors.');
            }
        }

        return $this->render('experience/new.html.twig', [
            'experience' => $experience,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_experience_show', methods: ['GET'])]
    public function show(Experience $experience, CommentaireRepository $commentaireRepository): Response
    {
        $commentForm = $this->createForm(CommentaireType::class, new Commentaire(), [
            'action' => $this->generateUrl('commentaire_add', ['id' => $experience->getId()])
        ]);

        // Get comments for this experience
        $commentaires = $commentaireRepository->findBy(
            ['experience' => $experience],
            ['dateCreation' => 'DESC']
        );

        return $this->render('experience/show.html.twig', [
            'experience' => $experience,
            'commentForm' => $commentForm->createView(),
            'commentaires' => $commentaires
        ]);
    }

    #[Route('/{id}/edit', name: 'app_experience_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Experience $experience, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExperienceType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Update air quality data if location changed
                $location = $experience->getLieu();
                if ($location) {
                    try {
                        $airQualityData = $this->airQualityService->getAirQuality($location);
                        $experience->setAirQualityData($airQualityData);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Could not update air quality data, but your experience will be saved.');
                    }
                }

                $entityManager->flush();
                $this->addFlash('success', 'Experience updated successfully!');
                return $this->redirectToRoute('app_experience_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the experience. Please try again.');
            }
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Please check the form for errors.');
        }

        return $this->render('experience/edit.html.twig', [
            'experience' => $experience,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_experience_delete', methods: ['POST'])]
    public function delete(Request $request, Experience $experience, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$experience->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($experience);
                $entityManager->flush();
                $this->addFlash('success', 'Experience deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the experience.');
            }
        }

        return $this->redirectToRoute('app_experience_index', [], Response::HTTP_SEE_OTHER);
    }
}
