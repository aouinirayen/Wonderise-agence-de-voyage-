<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Rating;
use App\Entity\Monument;
use App\Entity\Art;
use App\Entity\TraditionalFood;
use App\Entity\Selebrity;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use App\Repository\RatingRepository;
use App\Service\ImageWatermarkService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CountryController extends AbstractController
{
    private $entityManager;
    private $countryRepository;
    private $imageWatermarkService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CountryRepository $countryRepository,
        ImageWatermarkService $imageWatermarkService
    ) {
        $this->entityManager = $entityManager;
        $this->countryRepository = $countryRepository;
        $this->imageWatermarkService = $imageWatermarkService;
    }

    // Front-office routes
    #[Route('/country', name: 'app_country_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->countryRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Current page number, defaults to 1
            2 // Items per page
        );

        return $this->render('country/index.html.twig', [
            'countries' => $pagination,
            'pagination' => $pagination
        ]);
    }

    #[Route('/country/{id}', name: 'app_country_show', methods: ['GET'])]
    public function show(Country $country): Response
    {
        return $this->render('country/show.html.twig', [
            'country' => $country,
        ]);
    }

    #[Route('/country/{id}/rate/{type}', name: 'app_country_rate')]
    public function rate(
        Country $country,
        string $type,
        EntityManagerInterface $entityManager,
        RatingRepository $ratingRepository
    ): Response
    {
        $rating = new Rating();
        $rating->setCountry($country);
        $rating->setIsLike($type === 'like');
        
        $entityManager->persist($rating);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf('You %s %s!', $type === 'like' ? 'liked' : 'disliked', $country->getName())
        );
        
        return $this->redirectToRoute('app_country_index');
    }

    #[Route('/country/{country}/like-count', name: 'app_country_like_count')]
    public function getLikeCount(Country $country, RatingRepository $ratingRepository): Response
    {
        return new Response((string) $ratingRepository->countLikes($country));
    }

    #[Route('/country/{country}/dislike-count', name: 'app_country_dislike_count')]
    public function getDislikeCount(Country $country, RatingRepository $ratingRepository): Response
    {
        return new Response((string) $ratingRepository->countDislikes($country));
    }

    #[Route('/api/search', name: 'api_country_search', methods: ['GET'])]
    public function searchCountries(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');
        
        $qb = $this->countryRepository->createQueryBuilder('c')
            ->where('LOWER(c.name) LIKE LOWER(:searchTerm)')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.name', 'ASC');
        
        $countries = $qb->getQuery()->getResult();

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        
        return $this->json([
            'success' => true,
            'countries' => array_map(function($country) {
                return [
                    'id' => $country->getId(),
                    'name' => $country->getName(),
                    'img' => $country->getImg(),
                    'description' => $country->getDescription(),
                    'url' => $this->generateUrl('app_country_show', ['id' => $country->getId()])
                ];
            }, $countries)
        ], 200, [], ['groups' => ['country:read']]);
    }

    #[Route('/api/{id}/search', name: 'api_country_items_search', methods: ['GET'])]
    public function searchCountryItems(Request $request, Country $country): Response
    {
        $searchTerm = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        
        $results = [];
        $repository = null;
        $routeName = '';
        
        try {
            switch ($type) {
                case 'monuments':
                    $repository = $this->entityManager->getRepository(Monument::class);
                    $routeName = 'app_monument_show';
                    break;
                case 'arts':
                    $repository = $this->entityManager->getRepository(Art::class);
                    $routeName = 'app_art_show';
                    break;
                case 'traditionalfoods':
                    $repository = $this->entityManager->getRepository(TraditionalFood::class);
                    $routeName = 'app_traditional_food_show';
                    break;
                case 'celebrities':
                    $repository = $this->entityManager->getRepository(Selebrity::class);
                    $routeName = 'app_celebrity_show';
                    break;
                default:
                    throw new \Exception('Invalid type specified: ' . $type);
            }

            if (!$repository) {
                throw new \Exception('Repository not found for type: ' . $type);
            }

            $qb = $repository->createQueryBuilder('e')
                ->where('e.country = :country')
                ->andWhere('LOWER(e.name) LIKE LOWER(:searchTerm)')
                ->setParameter('country', $country)
                ->setParameter('searchTerm', '%' . $searchTerm . '%')
                ->orderBy('e.name', 'ASC');

            $items = $qb->getQuery()->getResult();
            
            $results = array_map(function($item) use ($routeName) {
                return [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'img' => $item->getImg(),
                    'description' => $item->getDescription(),
                    'url' => $this->generateUrl($routeName, ['id' => $item->getId()])
                ];
            }, $items);

            return $this->json([
                'success' => true,
                'items' => $results,
                'debug' => [
                    'type' => $type,
                    'searchTerm' => $searchTerm,
                    'itemCount' => count($items),
                    'routeName' => $routeName
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'type' => $type,
                    'searchTerm' => $searchTerm,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    // Back-office routes
    #[Route('/admin/country', name: 'app_country_admin_index', methods: ['GET'])]
    public function adminIndex(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->countryRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('country/boff/index1.html.twig', [
            'countries' => $pagination
        ]);
    }

    #[Route('/admin/country/new', name: 'app_country_admin_new', methods: ['GET', 'POST'])]
    public function adminNew(Request $request, ValidatorInterface $validator, SluggerInterface $slugger): Response
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('img')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('images_directory');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $imageFile->move($uploadDir, $newFilename);
                    $this->imageWatermarkService->addWatermark($uploadDir . '/' . $newFilename);
                    $country->setImg($newFilename);
                } catch (FileException $e) {
                    error_log('Error uploading file: ' . $e->getMessage());
                }
            }

            $errors = $validator->validate($country);
            $formErrors = [];
            foreach ($errors as $error) {
                if ($error->getPropertyPath() !== 'img') {
                    $form->get($error->getPropertyPath())->addError(new FormError($error->getMessage()));
                    $formErrors[] = $error;
                }
            }

            if (count($formErrors) === 0) {
                $this->entityManager->persist($country);
                $this->entityManager->flush();
                return $this->redirectToRoute('app_country_admin_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('country/boff/form1.html.twig', [
            'country' => $country,
            'form' => $form->createView(),
            'title' => 'Create new Country'
        ]);
    }

    #[Route('/admin/country/{id<\d+>}', name: 'app_country_admin_show', methods: ['GET'])]
    public function adminShow(int $id): Response
    {
        $country = $this->countryRepository->find($id);
        
        if (!$country) {
            throw $this->createNotFoundException('Country not found');
        }

        return $this->render('country/boff/show1.html.twig', [
            'country' => $country,
        ]);
    }
    
    #[Route('/admin/country/{id<\d+>}/edit', name: 'app_country_admin_edit', methods: ['GET', 'POST'])]
    public function adminEdit(Request $request, int $id, ValidatorInterface $validator, SluggerInterface $slugger): Response
    {
        $country = $this->countryRepository->find($id);
        
        if (!$country) {
            throw $this->createNotFoundException('Country not found');
        }

        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('img')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    if ($country->getImg()) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$country->getImg();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $uploadDir = $this->getParameter('images_directory');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $imageFile->move($uploadDir, $newFilename);
                    $this->imageWatermarkService->addWatermark($uploadDir . '/' . $newFilename);
                    $country->setImg($newFilename);
                } catch (FileException $e) {
                    error_log('Error uploading file: ' . $e->getMessage());
                }
            }

            $errors = $validator->validate($country);
            $formErrors = [];
            foreach ($errors as $error) {
                if ($error->getPropertyPath() !== 'img') {
                    $form->get($error->getPropertyPath())->addError(new FormError($error->getMessage()));
                    $formErrors[] = $error;
                }
            }

            if (count($formErrors) === 0) {
                $this->entityManager->flush();
                return $this->redirectToRoute('app_country_admin_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('country/boff/form1.html.twig', [
            'country' => $country,
            'form' => $form->createView(),
            'title' => 'Edit Country'
        ]);
    }
    
    #[Route('/admin/country/{id<\d+>}/delete', name: 'app_country_admin_delete', methods: ['POST'])]
    public function adminDelete(Request $request, int $id): Response
    {
        $country = $this->countryRepository->find($id);
        
        if (!$country) {
            throw $this->createNotFoundException('Country not found');
        }

        if ($this->isCsrfTokenValid('delete'.$country->getId(), $request->request->get('_token'))) {
            if ($country->getImg()) {
                $imagePath = $this->getParameter('images_directory').'/'.$country->getImg();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $this->entityManager->remove($country);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_country_admin_index');
    }
}