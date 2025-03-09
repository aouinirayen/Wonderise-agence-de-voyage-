<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Entity\OffrePhoto;
use App\Form\OffreType;
use App\WeatherBundle\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use App\Repository\OffreRepository;

final class OffreController extends AbstractController
{
    private $weatherService;
    private $slugger;

    private $cityTranslations = [
        'Londres' => 'London,GB',
        'Paris' => 'Paris,FR',
        'Rome' => 'Roma,IT',
        'Madrid' => 'Madrid,ES',
        'Berlin' => 'Berlin,DE',
        'Barcelone' => 'Barcelona,ES',
        'Amsterdam' => 'Amsterdam,NL',
        'Venise' => 'Venice,IT',
        'Florence' => 'Florence,IT',
        'Milan' => 'Milan,IT',
        'Lisbonne' => 'Lisbon,PT',
        'Marrakech' => 'Marrakech,MA',
        'Istanbul' => 'Istanbul,TR',
        'Athènes' => 'Athens,GR',
        'Nice' => 'Nice,FR',
        'Marseille' => 'Marseille,FR',
        'Lyon' => 'Lyon,FR',
        'Séville' => 'Seville,ES',
        'Valence' => 'Valencia,ES',
        'Munich' => 'Munich,DE',
        'Porto' => 'Porto,PT',
        'Dubai' => 'Dubai,AE',
        'Dubaï' => 'Dubai,AE',
        'Moscou' => 'Moscow,RU',
        'Saint-Pétersbourg' => 'Saint Petersburg,RU',
        'Sotchi' => 'Sochi,RU',
        'Kazan' => 'Kazan,RU',
        'Ekaterinbourg' => 'Yekaterinburg,RU',
        'Novossibirsk' => 'Novosibirsk,RU'
    ];

    public function __construct(WeatherService $weatherService, SluggerInterface $slugger)
    {
        $this->weatherService = $weatherService;
        $this->slugger = $slugger;
    }

    private function extractCityFromOffer(Offre $offre): ?string 
    {
        $titre = $offre->getTitre();
        foreach ($this->cityTranslations as $frenchCity => $englishCity) {
            if (stripos($titre, $frenchCity) !== false) {
                return $frenchCity;
            }
        }
        
        return 'Paris'; // Par défaut, on retourne Paris si aucune ville n'est trouvée
    }

    #[Route('/offre', name: 'app_offres')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $offres = $entityManager->getRepository(Offre::class)->findAll();

        // Ajouter les données météo pour chaque offre
        foreach ($offres as $offre) {
            $city = $this->extractCityFromOffer($offre);
            if ($city) {
                try {
                    $weather = $this->weatherService->getWeatherForCity($city);
                    $offre->setWeatherData($weather);
                } catch (\Exception $e) {
                    // En cas d'erreur, on continue sans données météo
                }
            }
        }

        return $this->render('FrontOffice/Offre/index.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/offre/{id}', name: 'app_offre_show', methods: ['GET'])]
    public function show(Offre $offre): Response
    {
        $weather = null;
        $city = $this->extractCityFromOffer($offre);
        
        if ($city) {
            $weather = $this->weatherService->getWeatherForCity($city);
        }

        return $this->render('FrontOffice/Offre/show.html.twig', [
            'offre' => $offre,
            'weather' => $weather
        ]);
    }

    #[Route('/admin/offre', name: 'admin_offre_index', methods: ['GET'])]
    public function adminIndex(EntityManagerInterface $entityManager): Response
    {
        $offreRepository = $entityManager->getRepository(Offre::class);
        $offres = $offreRepository->findAll();

        return $this->render('BackOffice/Offre/index.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/admin/offre/{id}', name: 'admin_offre_show', methods: ['GET'])]
    public function showAdmin(Offre $offre): Response
    {
        return $this->render('BackOffice/Offre/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/admin/new', name: 'admin_offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image principale
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $offre->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }
            }

            // Gestion des photos additionnelles
            $additionalPhotos = $form->get('additionalPhotos')->getData();
            foreach ($additionalPhotos as $photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                try {
                    $photo->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );

                    $offrePhoto = new OffrePhoto();
                    $offrePhoto->setOffre($offre);
                    $offrePhoto->setFilename($newFilename);
                    $entityManager->persist($offrePhoto);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload d\'une photo additionnelle');
                }
            }

            $entityManager->persist($offre);
            $entityManager->flush();

            return $this->redirectToRoute('admin_offre_index');
        }

        return $this->render('BackOffice/Offre/new.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }

    #[Route('/admin/offre/{id}/edit', name: 'admin_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image principale
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image si elle existe
                    if ($offre->getImage()) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$offre->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $offre->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }
            }

            // Gestion des photos additionnelles
            $additionalPhotos = $form->get('additionalPhotos')->getData();
            foreach ($additionalPhotos as $photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                try {
                    $photo->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );

                    $offrePhoto = new OffrePhoto();
                    $offrePhoto->setOffre($offre);
                    $offrePhoto->setFilename($newFilename);
                    $entityManager->persist($offrePhoto);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload d\'une photo additionnelle');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('admin_offre_index');
        }

        return $this->render('BackOffice/Offre/edit.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }

    #[Route('/admin/offre/{id}/delete', name: 'admin_offre_delete', methods: ['POST'])]
    public function delete(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            // Supprimer l'image principale
            if ($offre->getImage()) {
                $imagePath = $this->getParameter('images_directory').'/'.$offre->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Supprimer les photos additionnelles
            foreach ($offre->getPhotos() as $photo) {
                $photoPath = $this->getParameter('images_directory').'/'.$photo->getFilename();
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            $entityManager->remove($offre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_offre_index');
    }

    #[Route('/admin/offre/search', name: 'admin_offre_search', methods: ['GET'])]
    public function search(Request $request, OffreRepository $offreRepository): JsonResponse
    {
        $search = $request->query->get('search');
        $maxPrice = $request->query->get('maxPrice') ? (float)$request->query->get('maxPrice') : null;
        $minPlaces = $request->query->get('minPlaces') ? (int)$request->query->get('minPlaces') : null;

        $offres = $offreRepository->searchOffres($search, $maxPrice, $minPlaces);

        $results = [];
        foreach ($offres as $offre) {
            $results[] = [
                'id' => $offre->getId(),
                'titre' => $offre->getTitre(),
                'prix' => $offre->getPrix(),
                'placesDisponibles' => $offre->getPlacesDisponibles(),
                'image' => $offre->getImage()
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/offre/ajax-search', name: 'offre_ajax_search', methods: ['GET'])]
    public function searchAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        $qb = $entityManager->createQueryBuilder();
        $qb->select('o')
           ->from(Offre::class, 'o')
           ->where('o.titre LIKE :query')
           ->orWhere('o.description LIKE :query')
           ->setParameter('query', '%'.$query.'%')
           ->orderBy('o.id', 'DESC');
        
        $offres = $qb->getQuery()->getResult();
        
        $results = [];
        foreach ($offres as $offre) {
            $results[] = [
                'id' => $offre->getId(),
                'titre' => $offre->getTitre(),
                'description' => $offre->getDescription(),
                'prix' => $offre->getPrix(),
                'placesDisponibles' => $offre->getPlacesDisponibles(),
                'image' => $offre->getImage(),
                'dateDepart' => $offre->getDateDepart() ? $offre->getDateDepart()->format('d/m/Y') : '',
                'dateRetour' => $offre->getDateRetour() ? $offre->getDateRetour()->format('d/m/Y') : ''
            ];
        }
        
        return new JsonResponse($results);
    }

    #[Route('/offre/search-ajax', name: 'offre_search_ajax', methods: ['GET'])]
    public function searchAjax2(Request $request, OffreRepository $offreRepository): JsonResponse
    {
        $query = $request->query->get('q');
        
        $offres = $offreRepository->createQueryBuilder('o')
            ->where('o.titre LIKE :query')
            ->orWhere('o.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->getQuery()
            ->getResult();
        
        $html = $this->renderView('FrontOffice/Offre/_offers_list.html.twig', [
            'offres' => $offres
        ]);
        
        return new JsonResponse(['html' => $html]);
    }

    #[Route('/offre/{id}/rate', name: 'offre_rate', methods: ['POST'])]
    public function rate(Request $request, Offre $offre): JsonResponse
    {
        $rating = $request->request->get('rating');
        
        // Ici vous pouvez ajouter la logique pour sauvegarder le rating
        // Pour l'instant, on renvoie juste un succès
        return new JsonResponse(['success' => true, 'rating' => $rating]);
    }
}
