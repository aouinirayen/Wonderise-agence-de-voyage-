<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Notification;
use App\Form\UserProfileType;
use App\Repository\UserRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/user')]
class UserController extends AbstractController
{
    private $notificationRepository;
    private $entityManager;

    public function __construct(NotificationRepository $notificationRepository, EntityManagerInterface $entityManager)
    {
        $this->notificationRepository = $notificationRepository;
        $this->entityManager = $entityManager;
    }
    #[Route('', name: 'user_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->redirectToRoute('user_profile');
    }

    #[Route('/notifications', name: 'user_notifications')]
    public function notifications(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $type = $request->query->get('type');
        $notifications = $type ? 
            $this->notificationRepository->findByUserAndType($user, $type) :
            $this->notificationRepository->findUnreadByUser($user);

        return $this->render('user/notifications.html.twig', [
            'notifications' => $notifications,
            'activeType' => $type ?: 'all'
        ]);
    }

    #[Route('/notifications/mark-all-read', name: 'user_notifications_mark_all_read', methods: ['POST'])]
    public function markAllNotificationsAsRead(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->notificationRepository->markAllAsRead($user);
        $this->entityManager->flush();

        return $this->redirectToRoute('user_notifications');
    }

    #[Route('/notifications/settings', name: 'user_notifications_settings', methods: ['GET', 'POST'])]
    public function notificationSettings(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $preferences = $request->request->all()['notifications'] ?? [];
            $user->setNotificationPreferences([
                'email' => [
                    'bookings' => isset($preferences['email']['bookings']),
                    'offers' => isset($preferences['email']['offers'])
                ],
                'push' => [
                    'bookings' => isset($preferences['push']['bookings']),
                    'offers' => isset($preferences['push']['offers'])
                ]
            ]);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Notification settings updated successfully');
            return $this->redirectToRoute('user_notifications_settings');
        }

        return $this->render('user/notification_settings.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle profile photo upload
                $profilePhoto = $form->get('profilePhoto')->getData();
                if ($profilePhoto) {
                    $originalFilename = pathinfo($profilePhoto->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $originalFilename.'-'.uniqid().'.'.$profilePhoto->guessExtension();

                    // Create directory if it doesn't exist
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile_photos';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    // Delete old profile photo if exists
                    $oldPhoto = $user->getProfilePhoto();
                    if ($oldPhoto && $oldPhoto !== 'default-avatar.png') {
                        $oldPhotoPath = $uploadDir . '/' . $oldPhoto;
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }

                    // Move the new photo
                    $profilePhoto->move($uploadDir, $newFilename);
                    $user->setProfilePhoto($newFilename);
                }

                // Update user data from form
                $user->setNom($form->get('nom')->getData());
                $user->setPrenom($form->get('prenom')->getData());
                $user->setUsername($form->get('username')->getData());
                $user->setEmail($form->get('email')->getData());
                $user->setBirthDate($form->get('birthDate')->getData());
                $user->setGender($form->get('gender')->getData());
                $user->setNationality($form->get('nationality')->getData());
                $user->setPhone($form->get('phone')->getData());
                $user->setStreet($form->get('street')->getData());
                $user->setPostalCode($form->get('postalCode')->getData());
                $user->setCity($form->get('city')->getData());
                $user->setCountry($form->get('country')->getData());

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Profile updated successfully!');
                return $this->redirectToRoute('user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving your profile. Please try again.');
            }
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Please check the form for errors.');
        }

        return $this->render('user/edit_profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users', name: 'admin_users_list')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $clients = $userRepository->findAllClients();

        return $this->render('user/list.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!in_array('ROLE_CLIENT', $user->getRoles())) {
            throw $this->createAccessDeniedException('You can only delete client users.');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully!');

        return $this->redirectToRoute('admin_users_list');
    }
}