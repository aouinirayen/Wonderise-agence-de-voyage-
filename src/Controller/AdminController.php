<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/home', name: 'admin_home')]
    public function home(UserRepository $userRepository): Response
    {
        // Récupérer tous les utilisateurs
        $users = $userRepository->findAll();
        
        // Préparer les données pour la carte
        $userLocations = [];
        foreach ($users as $user) {
            $countryCode = $user->getCountryCode();
            if ($countryCode) {
                if (!isset($userLocations[$countryCode])) {
                    $userLocations[$countryCode] = 1;
                } else {
                    $userLocations[$countryCode]++;
                }
            }
        }

        // Ajouter des données de test si aucun utilisateur n'a de localisation
        if (empty($userLocations)) {
            $userLocations = [
                'us' => 15,
                'fr' => 8,
                'gb' => 5,
                'de' => 7,
                'tn' => 12,
                'ca' => 6,
                'br' => 9,
                'au' => 4,
                'in' => 10,
                'cn' => 20
            ];
        }

        return $this->render('admin/home.html.twig', [
            'userLocations' => json_encode($userLocations)
        ]);
    }

    #[Route('/search-users', name: 'admin_search_users')]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $query = $request->query->get('q', '');
        $filters = [
            'role' => $request->query->get('role'),
            'country' => $request->query->get('country'),
            'status' => $request->query->get('status'),
            'dateRange' => $request->query->get('dateRange')
        ];

        $qb = $entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        // Recherche principale
        if ($query) {
            $qb->andWhere('u.email LIKE :query OR u.username LIKE :query OR u.firstName LIKE :query OR u.lastName LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Filtres
        if ($filters['role']) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%"' . $filters['role'] . '"%');
        }

        if ($filters['country']) {
            $qb->andWhere('u.countryCode = :country')
               ->setParameter('country', $filters['country']);
        }

        if ($filters['status']) {
            $qb->andWhere('u.isActive = :status')
               ->setParameter('status', $filters['status'] === 'active');
        }

        if ($filters['dateRange']) {
            $dates = explode(',', $filters['dateRange']);
            if (count($dates) === 2) {
                $qb->andWhere('u.createdAt BETWEEN :start AND :end')
                   ->setParameter('start', new \DateTime($dates[0]))
                   ->setParameter('end', new \DateTime($dates[1]));
            }
        }

        $users = $qb->setMaxResults(10)
                   ->orderBy('u.lastName', 'ASC')
                   ->getQuery()
                   ->getResult();

        $results = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'text' => sprintf('%s %s (%s)', $user->getFirstName(), $user->getLastName(), $user->getUsername()),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'profilePhoto' => $user->getProfilePhotoUrl(),
                'country' => $user->getCountryCode(),
                'roles' => $user->getRoles(),
                'status' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d')
            ];
        }, $users);

        return new JsonResponse($results);
    }

    #[Route('/users', name: 'admin_users')]
    public function usersList(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $this->render('admin/users/list.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/users/new', name: 'admin_user_new')]
    public function newUser(Request $request): Response
    {
        // TODO: Implement user creation
        return $this->render('admin/users/new.html.twig');
    }

    #[Route('/stats', name: 'admin_stats')]
    public function statistics(): Response
    {
        // TODO: Implement statistics
        return $this->render('admin/stats.html.twig');
    }

    #[Route('/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        // TODO: Implement settings
        return $this->render('admin/settings.html.twig');
    }

    #[Route('/profile', name: 'admin_profile')]
    public function viewProfile(): Response
    {
        return $this->render('admin/profile/view.html.twig');
    }

    #[Route('/profile/edit', name: 'admin_profile_edit')]
    public function editProfile(): Response
    {
        return $this->render('admin/profile/edit.html.twig');
    }

    #[Route('/client/list', name: 'admin_client_list')]
    public function clientList(): Response
    {
        return $this->render('admin/client/list.html.twig');
    }
}
