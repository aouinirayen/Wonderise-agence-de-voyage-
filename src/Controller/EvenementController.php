<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Country;
use App\Entity\Commentaire;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Service\WeatherService;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    private $logger;
    private $httpClient;
    private $csrfTokenManager;

    public function __construct(LoggerInterface $logger, HttpClientInterface $httpClient, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    private function geocodeAddress(string $address): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://nominatim.openstreetmap.org/search',
                [
                    'query' => [
                        'q' => $address,
                        'format' => 'json',
                        'limit' => 1
                    ],
                    'headers' => [
                        'User-Agent' => 'Wonderise/1.0'
                    ]
                ]
            );

            $data = $response->toArray();
            if (count($data) > 0) {
                return [
                    'latitude' => (float)$data[0]['lat'],
                    'longitude' => (float)$data[0]['lon']
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Geocoding error: ' . $e->getMessage());
        }

        return ['latitude' => null, 'longitude' => null];
    }

    ///////////
    #[Route('/yr', name: 'app_evenement_index1', methods: ['GET'])]
    public function index1(Request $request, EvenementRepository $evenementRepository): Response
    {
        $searchTerm = $request->query->get('searchTerm');
        $categorie = $request->query->get('categorie');
        $pays = $request->query->get('pays');
        $status = $request->query->get('status');

        $queryBuilder = $evenementRepository->createQueryBuilder('e');

        // Récupérer la liste unique des pays
        $paysQuery = $evenementRepository->createQueryBuilder('e')
            ->select('DISTINCT e.pays')
            ->where('e.pays IS NOT NULL')
            ->orderBy('e.pays', 'ASC')
            ->getQuery();
        $paysList = array_column($paysQuery->getResult(), 'pays');

        // Récupérer la liste unique des catégories
        $categoriesQuery = $evenementRepository->createQueryBuilder('e')
            ->select('DISTINCT e.categorie')
            ->where('e.categorie IS NOT NULL')
            ->orderBy('e.categorie', 'ASC')
            ->getQuery();
        $categories = array_column($categoriesQuery->getResult(), 'categorie');

        // Appliquer les filtres
        if ($searchTerm) {
            $queryBuilder->andWhere('e.nom LIKE :searchTerm OR e.description LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($categorie) {
            $queryBuilder->andWhere('e.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        if ($pays) {
            $queryBuilder->andWhere('e.pays = :pays')
                ->setParameter('pays', $pays);
        }

        if ($status) {
            $queryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        $evenements = $queryBuilder->orderBy('e.date', 'ASC')->getQuery()->getResult();

        return $this->render('evenement/boff/index.html.twig', [
            'evenements' => $evenements,
            'paysList' => $paysList,
            'categories' => $categories,
        ]);
    }

    ///////////
    #[Route('/boff/{id}', name: 'app_evenement_show1', methods: ['GET'])]
    public function show1(Evenement $evenement): Response
    {
        return $this->render('evenement/boff/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    ///////////
    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $searchTerm = $request->query->get('searchTerm');
        $categorie = $request->query->get('categorie');
        $pays = $request->query->get('pays');
        $status = $request->query->get('status');

        $queryBuilder = $evenementRepository->createQueryBuilder('e');

        // Récupérer la liste unique des pays
        $paysQuery = $evenementRepository->createQueryBuilder('e')
            ->select('DISTINCT e.pays')
            ->where('e.pays IS NOT NULL')
            ->orderBy('e.pays', 'ASC')
            ->getQuery();
        $paysList = array_column($paysQuery->getResult(), 'pays');

        // Récupérer la liste unique des catégories
        $categoriesQuery = $evenementRepository->createQueryBuilder('e')
            ->select('DISTINCT e.categorie')
            ->where('e.categorie IS NOT NULL')
            ->orderBy('e.categorie', 'ASC')
            ->getQuery();
        $categories = array_column($categoriesQuery->getResult(), 'categorie');

        // Appliquer les filtres
        if ($searchTerm) {
            $queryBuilder->andWhere('e.nom LIKE :searchTerm OR e.description LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($categorie) {
            $queryBuilder->andWhere('e.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        if ($pays) {
            $queryBuilder->andWhere('e.pays = :pays')
                ->setParameter('pays', $pays);
        }

        if ($status) {
            $queryBuilder->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        $evenements = $queryBuilder->orderBy('e.date', 'ASC')->getQuery()->getResult();

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'paysList' => $paysList,
            'categories' => $categories,
        ]);
    }

    #[Route('/favoris', name: 'app_evenement_favoris', methods: ['GET'])]
    public function favoris(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findBy(['isFavorite' => true]);
        return $this->render('evenement/favoris.html.twig', [
            'evenements' => $evenements
        ]);
    }

    #[Route('/favorite/{id}', name: 'app_evenement_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $evenement = $entityManager->getRepository(Evenement::class)->find($id);
        
        if (!$evenement) {
            return new JsonResponse(['success' => false, 'error' => 'Événement non trouvé'], 404);
        }

        try {
            $evenement->setIsFavorite(!$evenement->getIsFavorite());
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'isFavorite' => $evenement->getIsFavorite()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Une erreur est survenue'], 500);
        }
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $evenement = new Evenement();
        $evenement->setDate(new \DateTime()); // Définir une date par défaut
        $evenement->setHeure(new \DateTime()); // Définir l'heure actuelle par défaut
        $evenement->setStatus('actif'); // Définir le statut par défaut
        $evenement->setIsAnnule(false); // Définir isAnnule par défaut
        
        $form = $this->createForm(EvenementType::class, $evenement);
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
                    $evenement->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
                }
            } else {
                // Si aucune photo n'est fournie, utiliser une photo par défaut
                $evenement->setPhoto('default-event.jpg');
            }
            
            // Geocode the address
            $coordinates = $this->geocodeAddress($evenement->getLieu());
            $evenement->setLatitude($coordinates['latitude']);
            $evenement->setLongitude($coordinates['longitude']);

            $entityManager->persist($evenement);
            $entityManager->flush();
            return $this->redirectToRoute('app_evenement_index1');
        }

        return $this->render('evenement/boff/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement, WeatherService $weatherService, LoggerInterface $logger): Response
    {
        $weather = null;
        $weatherError = null;

        try {
            if ($evenement->getLieu()) {
                $lieu = trim($evenement->getLieu());
                // Si le lieu contient une virgule, prendre la dernière partie (généralement la ville)
                if (str_contains($lieu, ',')) {
                    $parts = array_map('trim', explode(',', $lieu));
                    $lieu = end($parts);
                }
                $weather = $weatherService->getWeatherForLocation($lieu);
                $logger->info('Weather fetched successfully', ['lieu' => $lieu]);
            }
        } catch (\Exception $e) {
            $weatherError = $e->getMessage();
            $logger->error('Weather fetch error', [
                'lieu' => $evenement->getLieu(),
                'error' => $e->getMessage()
            ]);
        }

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'weather' => $weather,
            'weatherError' => $weatherError
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
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

                    // Supprimer l'ancienne photo si elle existe
                    $oldPhoto = $evenement->getPhoto();
                    if ($oldPhoto && $oldPhoto !== 'default-event.jpg') {
                        $oldPhotoPath = $uploadDir . '/' . $oldPhoto;
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }

                    $photoFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    $evenement->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo: ' . $e->getMessage());
                }
            }

            // Geocode the address
            $coordinates = $this->geocodeAddress($evenement->getLieu());
            $evenement->setLatitude($coordinates['latitude']);
            $evenement->setLongitude($coordinates['longitude']);

            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié avec succès.');
            return $this->redirectToRoute('app_evenement_index1');
        }

        return $this->render('evenement/boff/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_evenement_confirm', methods: ['GET'])]
    public function confirm(Evenement $evenement): Response
    {
        return $this->render('evenement/confirm.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/delete-confirm', name: 'app_evenement_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(Evenement $evenement): Response
    {
        return $this->render('evenement/delete_confirm.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_evenement_index1');
    }

    #[Route('/{id}/interesse', name: 'app_evenement_interesse', methods: ['POST'])]
    public function toggleInteresse(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        try {
            $evenement->setIsInterested(!$evenement->isInterested());
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'interested' => $evenement->isInterested()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }

    #[Route('/{id}/like', name: 'app_evenement_like', methods: ['POST'])]
    public function toggleLike(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        try {
            $evenement->setIsLiked(!$evenement->isLiked());
            if ($evenement->isLiked()) {
                $evenement->setLikesCount($evenement->getLikesCount() + 1);
            } else {
                $evenement->setLikesCount(max(0, $evenement->getLikesCount() - 1));
            }
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'liked' => $evenement->isLiked(),
                'likesCount' => $evenement->getLikesCount()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }

    #[Route('/{id}/comment', name: 'app_evenement_comment', methods: ['POST'])]
    public function addComment(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->logger->info('Début de addComment');
        
        if (!$request->isXmlHttpRequest()) {
            $this->logger->error('La requête n\'est pas une requête AJAX');
            return new JsonResponse(['success' => false, 'error' => 'Requête invalide'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $this->logger->info('Contenu reçu:', ['data' => $data]);
        
        $content = $data['content'] ?? null;

        if (!$content) {
            $this->logger->error('Contenu du commentaire manquant');
            return new JsonResponse(['success' => false, 'error' => 'Le contenu du commentaire est requis'], 400);
        }

        $commentaire = new Commentaire();
        $commentaire->setCommentaire($content);
        $commentaire->setDate(new \DateTime());
        $commentaire->setEvenement($evenement);

        try {
            $entityManager->persist($commentaire);
            $entityManager->flush();
            
            $this->logger->info('Commentaire enregistré avec succès');

            return new JsonResponse([
                'success' => true,
                'comment' => [
                    'content' => $commentaire->getCommentaire(),
                    'createdAt' => $commentaire->getDate()->format('d/m/Y H:i')
                ],
                'totalComments' => count($evenement->getCommentaires())
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement:', ['error' => $e->getMessage()]);
            return new JsonResponse(['success' => false, 'error' => 'Une erreur est survenue lors de l\'enregistrement du commentaire'], 500);
        }
    }

    private function createCsrfToken(string $intention): string
    {
        return $this->csrfTokenManager->getToken($intention)->getValue();
    }
}
