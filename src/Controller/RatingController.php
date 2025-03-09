<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Experience;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/experience/{id}/rate', name: 'app_rating_rate', methods: ['POST'])]
    public function rate(Request $request, Experience $experience, EntityManagerInterface $entityManager): JsonResponse
    {
        // Verify CSRF token
        $submittedToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('rate', $submittedToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON');
            }

            $type = $data['type'] ?? 'rating';
            $value = $data['value'] ?? null;

            // Validate input
            if (!in_array($type, ['like', 'dislike', 'rating'])) {
                return new JsonResponse(['error' => 'Invalid rating type'], Response::HTTP_BAD_REQUEST);
            }

            if ($type === 'rating' && ($value < 1 || $value > 5)) {
                return new JsonResponse(['error' => 'Rating must be between 1 and 5'], Response::HTTP_BAD_REQUEST);
            }

            // For likes/dislikes, set value to 1
            if ($type === 'like' || $type === 'dislike') {
                $value = 1;
            }

            // Check if user already rated
            $existingRating = $entityManager->getRepository(Rating::class)->findOneBy([
                'experience' => $experience,
                'userId' => 'user', // Replace with actual user ID when authentication is implemented
                'type' => $type
            ]);

            if ($existingRating) {
                // If same type and value, remove the rating (toggle)
                if ($existingRating->getValue() === $value) {
                    $entityManager->remove($existingRating);
                    $entityManager->flush();
                    return new JsonResponse(['message' => 'Rating removed']);
                }
                // Otherwise update the existing rating
                $existingRating->setValue($value);
                $entityManager->flush();
                return new JsonResponse(['message' => 'Rating updated']);
            }

            // Create new rating
            $rating = new Rating();
            $rating->setExperience($experience);
            $rating->setValue($value);
            $rating->setType($type);
            $rating->setUserId('user'); // Replace with actual user ID when authentication is implemented

            $entityManager->persist($rating);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Rating added']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while processing your request'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/experience/{id}/stats', name: 'app_rating_stats', methods: ['GET'])]
    public function getStats(Experience $experience, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $ratings = $entityManager->getRepository(Rating::class)->findBy(['experience' => $experience]);

            $stats = [
                'likes' => 0,
                'dislikes' => 0,
                'rating' => [
                    'average' => 0,
                    'count' => 0
                ]
            ];

            $ratingSum = 0;
            foreach ($ratings as $rating) {
                switch ($rating->getType()) {
                    case 'like':
                        $stats['likes']++;
                        break;
                    case 'dislike':
                        $stats['dislikes']++;
                        break;
                    case 'rating':
                        $stats['rating']['count']++;
                        $ratingSum += $rating->getValue();
                        break;
                }
            }

            if ($stats['rating']['count'] > 0) {
                $stats['rating']['average'] = round($ratingSum / $stats['rating']['count'], 1);
            }

            return new JsonResponse($stats);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while fetching stats'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
