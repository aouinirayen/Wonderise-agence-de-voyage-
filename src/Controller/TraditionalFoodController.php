<?php

namespace App\Controller;

use App\Entity\TraditionalFood;
use App\Form\TraditionalFoodType;
use App\Repository\TraditionalFoodRepository;
use App\Repository\CountryRepository;
use App\Service\ImageWatermarkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\QrCodeService;

#[Route('/traditional-food')]
class TraditionalFoodController extends AbstractController
{
    private $entityManager;
    private $traditionalFoodRepository;
    private $countryRepository;
    private $qrCodeService;
    private $imageWatermarkService;

    public function __construct(
        EntityManagerInterface $entityManager,
        TraditionalFoodRepository $traditionalFoodRepository,
        CountryRepository $countryRepository,
        QrCodeService $qrCodeService,
        ImageWatermarkService $imageWatermarkService
    ) {
        $this->entityManager = $entityManager;
        $this->traditionalFoodRepository = $traditionalFoodRepository;
        $this->countryRepository = $countryRepository;
        $this->qrCodeService = $qrCodeService;
        $this->imageWatermarkService = $imageWatermarkService;
    }
//////////
#[Route('/sean', name: 'app_traditional_food_index1', methods: ['GET'])]
public function index1(): Response
{
    return $this->render('traditional_food/boff/index.html.twig', [
        'traditional_foods' => $this->traditionalFoodRepository->findAll(),
    ]);
}






#[Route('/new', name: 'app_traditional_food_new1', methods: ['GET', 'POST'])]
public function new1(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $traditionalFood = new TraditionalFood();
    $form = $this->createForm(TraditionalFoodType::class, $traditionalFood);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        $errors = [];

      
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('img')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                // Create the directory if it doesn't exist
                $uploadDir = $this->getParameter('images_directory');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $imageFile->move($uploadDir, $newFilename);
                $this->imageWatermarkService->addWatermark($uploadDir . '/' . $newFilename);
                $traditionalFood->setImg($newFilename);
            } catch (FileException $e) {
                $errors['img'] = 'Error uploading file: ' . $e->getMessage();
            }
        }

        if (empty($errors) && $form->isValid()) {
            $entityManager->persist($traditionalFood);
            $entityManager->flush();

            return $this->redirectToRoute('app_traditional_food_index1', [], Response::HTTP_SEE_OTHER);
        }

        // Add errors to form
        foreach ($errors as $field => $message) {
            $form->get($field)->addError(new FormError($message));
        }
    }

    return $this->render('traditional_food/boff/form.html.twig', [
        'traditional_food' => $traditionalFood,
        'form' => $form,
        'title' => 'Create new Traditional Food'
    ]);
}



#[Route('/jhg/{id}', name: 'app_traditional_food_show1', methods: ['GET'])]
public function show1(TraditionalFood $traditionalFood): Response
{
    return $this->render('traditional_food/boff/show.html.twig', [
        'traditional_food' => $traditionalFood,
    ]);
}

#[Route('/{id}/edit1', name: 'app_traditional_food_edit1', methods: ['GET', 'POST'])]
public function edit1(Request $request, TraditionalFood $traditionalFood, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $form = $this->createForm(TraditionalFoodType::class, $traditionalFood);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $errors = [];

        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('img')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                // Create the directory if it doesn't exist
                $uploadDir = $this->getParameter('images_directory');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Delete old image if exists
                if ($traditionalFood->getImg()) {
                    $oldImagePath = $uploadDir.'/'.$traditionalFood->getImg();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $imageFile->move($uploadDir, $newFilename);
                $this->imageWatermarkService->addWatermark($uploadDir . '/' . $newFilename);
                $traditionalFood->setImg($newFilename);
            } catch (FileException $e) {
                $errors['img'] = 'Error uploading file: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $entityManager->flush();

            return $this->redirectToRoute('app_traditional_food_index1', [], Response::HTTP_SEE_OTHER);
        }

        // Add errors to form
        foreach ($errors as $field => $message) {
            $form->get($field)->addError(new FormError($message));
        }
    }

    return $this->render('traditional_food/boff/form.html.twig', [
        'traditional_food' => $traditionalFood,
        'form' => $form,
        'title' => 'Edit Traditional Food'
    ]);
}

#[Route('/{id}', name: 'app_traditional_food_delete1', methods: ['POST'])]
public function delete1(Request $request, TraditionalFood $traditionalFood): Response
{
    if ($this->isCsrfTokenValid('delete'.$traditionalFood->getId(), $request->request->get('_token'))) {
        // Delete image file if exists
        if ($traditionalFood->getImg()) {
            $imagePath = $this->getParameter('images_directory').'/'.$traditionalFood->getImg();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $this->entityManager->remove($traditionalFood);
        $this->entityManager->flush();
    }

    return $this->redirectToRoute('app_traditional_food_index1');
}

//////////
    #[Route('/', name: 'app_traditional_food_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('traditional_food/index.html.twig', [
            'traditional_foods' => $this->traditionalFoodRepository->findAll(),
        ]);
    }

    #[Route('/traditional/food/new', name: 'app_traditional_food_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $traditionalFood = new TraditionalFood();
        $form = $this->createForm(TraditionalFoodType::class, $traditionalFood);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $validator = Validation::createValidator();
            $errors = [];

            // Validate name
            $nameConstraints = [
                new Assert\NotBlank(['message' => 'Name cannot be empty']),
                new Assert\Length([
                    'min' => 2,
                    'max' => 255,
                    'minMessage' => 'Name must be at least {{ limit }} characters',
                    'maxMessage' => 'Name cannot exceed {{ limit }} characters'
                ]),
                new Assert\Regex([
                    'pattern' => '/^[A-Za-z0-9\s\-]+$/',
                    'message' => 'Name can only contain letters, numbers, spaces and hyphens'
                ])
            ];
            
            $nameViolations = $validator->validate($traditionalFood->getName(), $nameConstraints);
            if (count($nameViolations) > 0) {
                $errors['name'] = $nameViolations[0]->getMessage();
            }

            // Validate image URL
            $imgConstraints = [
                new Assert\NotBlank(['message' => 'Image URL cannot be empty']),
                new Assert\Url(['message' => 'Please enter a valid URL'])
            ];
            
            $imgViolations = $validator->validate($traditionalFood->getImg(), $imgConstraints);
            if (count($imgViolations) > 0) {
                $errors['img'] = $imgViolations[0]->getMessage();
            }

            // Validate description
            $descriptionConstraints = [
                new Assert\NotBlank(['message' => 'Description cannot be empty']),
                new Assert\Length([
                    'max' => 1000,
                    'maxMessage' => 'Description cannot exceed {{ limit }} characters'
                ])
            ];
            
            $descriptionViolations = $validator->validate($traditionalFood->getDescription(), $descriptionConstraints);
            if (count($descriptionViolations) > 0) {
                $errors['description'] = $descriptionViolations[0]->getMessage();
            }

            // Validate recipe
            $recipeConstraints = [
                new Assert\NotBlank(['message' => 'Recipe cannot be empty']),
                new Assert\Length([
                    'max' => 2000,
                    'maxMessage' => 'Recipe cannot exceed {{ limit }} characters'
                ])
            ];
            
            $recipeViolations = $validator->validate($traditionalFood->getRecipe(), $recipeConstraints);
            if (count($recipeViolations) > 0) {
                $errors['recipe'] = $recipeViolations[0]->getMessage();
            }

            // Validate country
            if (!$traditionalFood->getCountry()) {
                $errors['country'] = 'Please select a country';
            }

            if (empty($errors) && $form->isValid()) {
                $entityManager->persist($traditionalFood);
                $entityManager->flush();

                return $this->redirectToRoute('app_traditional_food_index', [], Response::HTTP_SEE_OTHER);
            }

            // Add errors to form
            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('traditional_food/boff/form.html.twig', [
            'traditional_food' => $traditionalFood,
            'form' => $form,
            'title' => 'Create new Traditional Food'
        ]);
    }

    #[Route('/h/{id}', name: 'app_traditional_food_show', methods: ['GET'])]
    public function show(TraditionalFood $traditionalFood): Response
    {
        // Générer une URL de recette (exemple avec une recherche Google)
        $recipeUrl = 'https://www.google.com/search?q=' . urlencode($traditionalFood->getName() . ' recipe');
        
        // Générer le QR code
        try {
            $qrCode = $this->qrCodeService->generateQrCode($recipeUrl);
        } catch (\Exception $e) {
            // En cas d'erreur, on continue sans QR code
            $qrCode = null;
        }

        return $this->render('traditional_food/show.html.twig', [
            'traditional_food' => $traditionalFood,
            'qr_code' => $qrCode,
            'recipe_url' => $recipeUrl
        ]);
    }

    #[Route('/{id}/edit', name: 'app_traditional_food_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TraditionalFood $traditionalFood, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TraditionalFoodType::class, $traditionalFood);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $validator = Validation::createValidator();
            $errors = [];

            // Validate name
            $nameConstraints = [
                new Assert\NotBlank(['message' => 'Name cannot be empty']),
                new Assert\Length([
                    'min' => 2,
                    'max' => 255,
                    'minMessage' => 'Name must be at least {{ limit }} characters',
                    'maxMessage' => 'Name cannot exceed {{ limit }} characters'
                ]),
                new Assert\Regex([
                    'pattern' => '/^[A-Za-z0-9\s\-]+$/',
                    'message' => 'Name can only contain letters, numbers, spaces and hyphens'
                ])
            ];
            
            $nameViolations = $validator->validate($traditionalFood->getName(), $nameConstraints);
            if (count($nameViolations) > 0) {
                $errors['name'] = $nameViolations[0]->getMessage();
            }

            // Validate image URL
            $imgConstraints = [
                new Assert\NotBlank(['message' => 'Image URL cannot be empty']),
                new Assert\Url(['message' => 'Please enter a valid URL'])
            ];
            
            $imgViolations = $validator->validate($traditionalFood->getImg(), $imgConstraints);
            if (count($imgViolations) > 0) {
                $errors['img'] = $imgViolations[0]->getMessage();
            }

            // Validate description
            $descriptionConstraints = [
                new Assert\NotBlank(['message' => 'Description cannot be empty']),
                new Assert\Length([
                    'max' => 1000,
                    'maxMessage' => 'Description cannot exceed {{ limit }} characters'
                ])
            ];
            
            $descriptionViolations = $validator->validate($traditionalFood->getDescription(), $descriptionConstraints);
            if (count($descriptionViolations) > 0) {
                $errors['description'] = $descriptionViolations[0]->getMessage();
            }

            // Validate recipe
            $recipeConstraints = [
                new Assert\NotBlank(['message' => 'Recipe cannot be empty']),
                new Assert\Length([
                    'max' => 2000,
                    'maxMessage' => 'Recipe cannot exceed {{ limit }} characters'
                ])
            ];
            
            $recipeViolations = $validator->validate($traditionalFood->getRecipe(), $recipeConstraints);
            if (count($recipeViolations) > 0) {
                $errors['recipe'] = $recipeViolations[0]->getMessage();
            }

            // Validate country
            if (!$traditionalFood->getCountry()) {
                $errors['country'] = 'Please select a country';
            }

            if (empty($errors) && $form->isValid()) {
                $entityManager->flush();
                return $this->redirectToRoute('app_traditional_food_index', [], Response::HTTP_SEE_OTHER);
            }

            // Add errors to form
            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('traditional_food/boff/form.html.twig', [
            'traditional_food' => $traditionalFood,
            'form' => $form,
            'title' => 'Edit Traditional Food'
        ]);
    }

    #[Route('/{id}', name: 'app_traditional_food_delete', methods: ['POST'])]
    public function delete(Request $request, TraditionalFood $traditionalFood): Response
    {
        if ($this->isCsrfTokenValid('delete'.$traditionalFood->getId(), $request->request->get('_token'))) {
            // Delete image file if exists
            if ($traditionalFood->getImg()) {
                $imagePath = $this->getParameter('images_directory').'/'.$traditionalFood->getImg();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $this->entityManager->remove($traditionalFood);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_traditional_food_index');
    }

    // API endpoints
    #[Route('/api/traditional-foods', name: 'api_traditional_food_index', methods: ['GET'])]
    public function apiIndex(): Response
    {
        $foods = $this->traditionalFoodRepository->findAll();
        return $this->json($foods, 200, [], ['groups' => 'food:read']);
    }

    #[Route('/api/traditional-foods/{id}', name: 'api_traditional_food_show', methods: ['GET'])]
    public function apiShow(TraditionalFood $traditionalFood): Response
    {
        return $this->json($traditionalFood, 200, [], ['groups' => 'food:read']);
    }

    #[Route('/api/traditional-foods', name: 'api_traditional_food_create', methods: ['POST'])]
    public function apiCreate(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $traditionalFood = new TraditionalFood();
        $traditionalFood->setName($data['name'] ?? null);
        $traditionalFood->setImg($data['img'] ?? null);
        $traditionalFood->setDescription($data['description'] ?? null);
        $traditionalFood->setRecipe($data['recipe'] ?? null);

        if (isset($data['country_id'])) {
            $country = $this->countryRepository->find($data['country_id']);
            if ($country) {
                $traditionalFood->setCountry($country);
            }
        }

        $this->entityManager->persist($traditionalFood);
        $this->entityManager->flush();

        return $this->json($traditionalFood, 201, [], ['groups' => 'food:read']);
    }

    #[Route('/api/traditional-foods/{id}', name: 'api_traditional_food_update', methods: ['PUT'])]
    public function apiUpdate(Request $request, TraditionalFood $traditionalFood): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $traditionalFood->setName($data['name']);
        }
        if (isset($data['img'])) {
            $traditionalFood->setImg($data['img']);
        }
        if (isset($data['description'])) {
            $traditionalFood->setDescription($data['description']);
        }
        if (isset($data['recipe'])) {
            $traditionalFood->setRecipe($data['recipe']);
        }
        if (isset($data['country_id'])) {
            $country = $this->countryRepository->find($data['country_id']);
            if ($country) {
                $traditionalFood->setCountry($country);
            }
        }

        $this->entityManager->flush();

        return $this->json($traditionalFood, 200, [], ['groups' => 'food:read']);
    }

    #[Route('/api/traditional-foods/{id}', name: 'api_traditional_food_delete', methods: ['DELETE'])]
    public function apiDelete(TraditionalFood $traditionalFood): Response
    {
        $this->entityManager->remove($traditionalFood);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
