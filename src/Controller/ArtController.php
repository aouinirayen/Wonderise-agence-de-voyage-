<?php

namespace App\Controller;

use App\Entity\Art;
use App\Form\ArtType;
use App\Repository\ArtRepository;
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

#[Route('/art')]
class ArtController extends AbstractController
{
    private $entityManager;
    private $artRepository;
    private $countryRepository;
    private $imageWatermarkService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ArtRepository $artRepository,
        CountryRepository $countryRepository,
        ImageWatermarkService $imageWatermarkService
    ) {
        $this->entityManager = $entityManager;
        $this->artRepository = $artRepository;
        $this->countryRepository = $countryRepository;
        $this->imageWatermarkService = $imageWatermarkService;
    }

    #[Route('/', name: 'app_art_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('art/index.html.twig', [
            'arts' => $this->artRepository->findAll(),
        ]);
    }

    #[Route('/se', name: 'app_art_index1', methods: ['GET'])]
    public function index1(): Response
    {
        return $this->render('art/boff/index.html.twig', [
            'arts' => $this->artRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_art_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $art = new Art();
        $form = $this->createForm(ArtType::class, $art);
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
                    $uploadDir = $this->getParameter('images_directory');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $imageFile->move($uploadDir, $newFilename);
                    $this->imageWatermarkService->addWatermark($uploadDir . '/' . $newFilename);
                    $art->setImg($newFilename);
                } catch (FileException $e) {
                    $errors['img'] = 'Error uploading file: ' . $e->getMessage();
                }
            }

            if (empty($errors)) {
                $entityManager->persist($art);
                $entityManager->flush();

                return $this->redirectToRoute('app_art_index1', [], Response::HTTP_SEE_OTHER);
            }

            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('art/boff/form.html.twig', [
            'art' => $art,
            'form' => $form,
            'title' => 'Create new Art'
        ]);
    }

    #[Route('/{id}/edit', name: 'app_art_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Art $art, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ArtType::class, $art);
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
                    if ($art->getImg()) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$art->getImg();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $this->imageWatermarkService->addWatermark($this->getParameter('images_directory') . '/' . $newFilename);
                    $art->setImg($newFilename);
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
            
            $nameViolations = $validator->validate($art->getName(), $nameConstraints);
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
            
            $descriptionViolations = $validator->validate($art->getDescription(), $descriptionConstraints);
            if (count($descriptionViolations) > 0) {
                $errors['description'] = $descriptionViolations[0]->getMessage();
            }

            // Validate country
            if (!$art->getCountry()) {
                $errors['country'] = 'Please select a country';
            }

            if (empty($errors) && $form->isValid()) {
                $entityManager->flush();
                return $this->redirectToRoute('app_art_index1', [], Response::HTTP_SEE_OTHER);
            }

            foreach ($errors as $field => $message) {
                $form->get($field)->addError(new FormError($message));
            }
        }

        return $this->render('art/boff/form.html.twig', [
            'art' => $art,
            'form' => $form,
            'title' => 'Edit Art'
        ]);
    }

    #[Route('/{id}', name: 'app_art_show1', methods: ['GET'])]
    public function show1(Art $art): Response
    {
        return $this->render('art/boff/show.html.twig', [
            'art' => $art,
        ]);
    }

    #[Route('/{id}', name: 'app_art_delete', methods: ['POST'])]
    public function delete(Request $request, Art $art): Response
    {
        if ($this->isCsrfTokenValid('delete'.$art->getId(), $request->request->get('_token'))) {
            // Delete the image file if it exists
            if ($art->getImg()) {
                $imagePath = $this->getParameter('images_directory').'/'.$art->getImg();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $this->entityManager->remove($art);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_art_index1', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('front/{id}', name: 'app_art_show', methods: ['GET'])]
    public function show(Art $art): Response
    {
        return $this->render('art/show.html.twig', [
            'art' => $art,
        ]);
    }

    // API endpoints
    #[Route('/api/arts', name: 'api_art_index', methods: ['GET'])]
    public function apiIndex(): Response
    {
        $arts = $this->artRepository->findAll();
        return $this->json($arts, 200, [], ['groups' => 'art:read']);
    }

    #[Route('/api/arts/{id}', name: 'api_art_show', methods: ['GET'])]
    public function apiShow(Art $art): Response
    {
        return $this->json($art, 200, [], ['groups' => 'art:read']);
    }

    #[Route('/api/arts', name: 'api_art_create', methods: ['POST'])]
    public function apiCreate(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $art = new Art();
        $art->setName($data['name'] ?? null);
        $art->setImg($data['img'] ?? null);
        $art->setDescription($data['description'] ?? null);

        if (isset($data['country_id'])) {
            $country = $this->countryRepository->find($data['country_id']);
            if ($country) {
                $art->setCountry($country);
            }
        }

        $this->entityManager->persist($art);
        $this->entityManager->flush();

        return $this->json($art, 201, [], ['groups' => 'art:read']);
    }

    #[Route('/api/arts/{id}', name: 'api_art_update', methods: ['PUT'])]
    public function apiUpdate(Request $request, Art $art): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $art->setName($data['name']);
        }
        if (isset($data['img'])) {
            $art->setImg($data['img']);
        }
        if (isset($data['description'])) {
            $art->setDescription($data['description']);
        }
        if (isset($data['country_id'])) {
            $country = $this->countryRepository->find($data['country_id']);
            if ($country) {
                $art->setCountry($country);
            }
        }

        $this->entityManager->flush();

        return $this->json($art, 200, [], ['groups' => 'art:read']);
    }

    #[Route('/api/arts/{id}', name: 'api_art_delete', methods: ['DELETE'])]
    public function apiDelete(Art $art): Response
    {
        $this->entityManager->remove($art);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}