<?php

namespace App\Controller;

use App\Entity\Monument;
use App\Form\MonumentType;
use App\Repository\MonumentRepository;
use App\Repository\CountryRepository;
use App\Service\ImageWatermarkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\FormError;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/monument')]
class MonumentController extends AbstractController
{
    private $entityManager;
    private $monumentRepository;
    private $countryRepository;
    private $imageWatermarkService;

    public function __construct(
        EntityManagerInterface $entityManager,
        MonumentRepository $monumentRepository,
        CountryRepository $countryRepository,
        ImageWatermarkService $imageWatermarkService
    ) {
        $this->entityManager = $entityManager;
        $this->monumentRepository = $monumentRepository;
        $this->countryRepository = $countryRepository;
        $this->imageWatermarkService = $imageWatermarkService;
    }

    ////////////////
    #[Route('/', name: 'app_monument_index1', methods: ['GET'])]
    public function index1(): Response
    {
        return $this->render('monument/boff/index.html.twig', [
            'monuments' => $this->monumentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_monument_new1', methods: ['GET', 'POST'])]
    public function new1(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $monument = new Monument();
        $form = $this->createForm(MonumentType::class, $monument);
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

                    $imageFile->move($uploadDir, $newFilename);
                    // Add watermark to the uploaded image
                    $this->imageWatermarkService->addWatermark($uploadDir . '/' . $newFilename);
                    $monument->setImg($newFilename);
                } catch (FileException $e) {
                    $errors['img'] = 'Error uploading file: ' . $e->getMessage();
                }
            }

            if (empty($errors)) {
                $entityManager->persist($monument);
                $entityManager->flush();

                return $this->redirectToRoute('app_monument_index1', [], Response::HTTP_SEE_OTHER);
            }

            // Add errors to form
            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('monument/boff/form.html.twig', [
            'monument' => $monument,
            'form' => $form,
            'title' => 'Create new Monument'
        ]);
    }

    #[Route('/jh/{id}', name: 'app_monument_show1', methods: ['GET'])]
    public function show1(Monument $monument): Response
    {
        return $this->render('monument/boff/show.html.twig', [
            'monument' => $monument,
        ]);
    }


    #[Route('/{id}/edit1', name: 'app_monument_edit1', methods: ['GET', 'POST'])]
    public function edit1(Request $request, Monument $monument, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(MonumentType::class, $monument);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $validator = Validation::createValidator();
            $errors = [];

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('img')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Delete old image if exists
                    if ($monument->getImg()) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$monument->getImg();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    // Add watermark to the uploaded image
                    $this->imageWatermarkService->addWatermark($this->getParameter('images_directory') . '/' . $newFilename);
                    $monument->setImg($newFilename);
                } catch (FileException $e) {
                    $errors['img'] = 'Error uploading file';
                }
            }

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
            
            $nameViolations = $validator->validate($monument->getName(), $nameConstraints);
            if (count($nameViolations) > 0) {
                $errors['name'] = $nameViolations[0]->getMessage();
            }

            // Validate description
            $descriptionConstraints = [
                new Assert\NotBlank(['message' => 'Description cannot be empty']),
                new Assert\Length([
                    'max' => 1000,
                    'maxMessage' => 'Description cannot exceed {{ limit }} characters'
                ])
            ];
            
            $descriptionViolations = $validator->validate($monument->getDescription(), $descriptionConstraints);
            if (count($descriptionViolations) > 0) {
                $errors['description'] = $descriptionViolations[0]->getMessage();
            }

            // Validate country
            if (!$monument->getCountry()) {
                $errors['country'] = 'Please select a country';
            }

            if (empty($errors) && $form->isValid()) {
                $entityManager->flush();
                return $this->redirectToRoute('app_monument_index1', [], Response::HTTP_SEE_OTHER);
            }

            // Add errors to form
            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('monument/boff/form.html.twig', [
            'monument' => $monument,
            'form' => $form,
            'title' => 'Edit Monument'
        ]);
    }

    #[Route('/s/{id}', name: 'app_monument_delete1', methods: ['POST'])]
    public function delete1(Request $request, Monument $monument): Response
    {
        if ($this->isCsrfTokenValid('delete'.$monument->getId(), $request->request->get('_token'))) {
            // Delete image file if exists
            if ($monument->getImg()) {
                $imagePath = $this->getParameter('images_directory').'/'.$monument->getImg();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $this->entityManager->remove($monument);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_monument_index1');
    }

    ////////////////
    #[Route('/', name: 'app_monument_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('monument/index.html.twig', [
            'monuments' => $this->monumentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_monument_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $monument = new Monument();
        $form = $this->createForm(MonumentType::class, $monument);
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
            
            $nameViolations = $validator->validate($monument->getName(), $nameConstraints);
            if (count($nameViolations) > 0) {
                $errors['name'] = $nameViolations[0]->getMessage();
            }

            // Validate image URL
            $imgConstraints = [
                new Assert\NotBlank(['message' => 'Image URL cannot be empty']),
                new Assert\Url(['message' => 'Please enter a valid URL'])
            ];
            
            $imgViolations = $validator->validate($monument->getImg(), $imgConstraints);
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
            
            $descriptionViolations = $validator->validate($monument->getDescription(), $descriptionConstraints);
            if (count($descriptionViolations) > 0) {
                $errors['description'] = $descriptionViolations[0]->getMessage();
            }

            // Validate country
            if (!$monument->getCountry()) {
                $errors['country'] = 'Please select a country';
            }

            if (empty($errors) && $form->isValid()) {
                $entityManager->persist($monument);
                $entityManager->flush();

                return $this->redirectToRoute('app_monument_index', [], Response::HTTP_SEE_OTHER);
            }

            // Add errors to form
            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('monument/boff/form.html.twig', [
            'monument' => $monument,
            'form' => $form,
            'title' => 'Create new Monument'
        ]);
    }

    #[Route('/{id}', name: 'app_monument_show', methods: ['GET'])]
    public function show(Monument $monument): Response
    {
        return $this->render('monument/show.html.twig', [
            'monument' => $monument,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_monument_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Monument $monument, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MonumentType::class, $monument);
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
            
            $nameViolations = $validator->validate($monument->getName(), $nameConstraints);
            if (count($nameViolations) > 0) {
                $errors['name'] = $nameViolations[0]->getMessage();
            }

            // Validate image URL
            $imgConstraints = [
                new Assert\NotBlank(['message' => 'Image URL cannot be empty']),
                new Assert\Url(['message' => 'Please enter a valid URL'])
            ];
            
            $imgViolations = $validator->validate($monument->getImg(), $imgConstraints);
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
            
            $descriptionViolations = $validator->validate($monument->getDescription(), $descriptionConstraints);
            if (count($descriptionViolations) > 0) {
                $errors['description'] = $descriptionViolations[0]->getMessage();
            }

            // Validate country
            if (!$monument->getCountry()) {
                $errors['country'] = 'Please select a country';
            }

            if (empty($errors) && $form->isValid()) {
                $entityManager->flush();
                return $this->redirectToRoute('app_monument_index', [], Response::HTTP_SEE_OTHER);
            }

            // Add errors to form
            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('monument/boff/form.html.twig', [
            'monument' => $monument,
            'form' => $form,
            'title' => 'Edit Monument'
        ]);
    }

    #[Route('/{id}', name: 'app_monument_delete', methods: ['POST'])]
    public function delete(Request $request, Monument $monument): Response
    {
        if ($this->isCsrfTokenValid('delete'.$monument->getId(), $request->request->get('_token'))) {
            // Delete image file if exists
            if ($monument->getImg()) {
                $imagePath = $this->getParameter('images_directory').'/'.$monument->getImg();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $this->entityManager->remove($monument);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_monument_index');
    }

    // API endpoints
    #[Route('/api/monuments', name: 'api_monument_index', methods: ['GET'])]
    public function apiIndex(): Response
    {
        $monuments = $this->monumentRepository->findAll();
        return $this->json($monuments, 200, [], ['groups' => 'monument:read']);
    }

    #[Route('/api/monuments/{id}', name: 'api_monument_show', methods: ['GET'])]
    public function apiShow(Monument $monument): Response
    {
        return $this->json($monument, 200, [], ['groups' => 'monument:read']);
    }

    #[Route('/api/monuments', name: 'api_monument_create', methods: ['POST'])]
    public function apiCreate(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $monument = new Monument();
        $monument->setName($data['name'] ?? null);
        $monument->setImg($data['img'] ?? null);
        $monument->setDescription($data['description'] ?? null);

        if (isset($data['country_id'])) {
            $country = $this->countryRepository->find($data['country_id']);
            if ($country) {
                $monument->setCountry($country);
            }
        }

        $this->entityManager->persist($monument);
        $this->entityManager->flush();

        return $this->json($monument, 201, [], ['groups' => 'monument:read']);
    }

    #[Route('/api/monuments/{id}', name: 'api_monument_update', methods: ['PUT'])]
    public function apiUpdate(Request $request, Monument $monument): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $monument->setName($data['name']);
        }
        if (isset($data['img'])) {
            $monument->setImg($data['img']);
        }
        if (isset($data['description'])) {
            $monument->setDescription($data['description']);
        }
        if (isset($data['country_id'])) {
            $country = $this->countryRepository->find($data['country_id']);
            if ($country) {
                $monument->setCountry($country);
            }
        }

        $this->entityManager->flush();

        return $this->json($monument, 200, [], ['groups' => 'monument:read']);
    }

    #[Route('/api/monuments/{id}', name: 'api_monument_delete', methods: ['DELETE'])]
    public function apiDelete(Monument $monument): Response
    {
        $this->entityManager->remove($monument);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
