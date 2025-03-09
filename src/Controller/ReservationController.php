<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Offre;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use App\Service\StripeService;
use App\Service\EmailService;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    private $entityManager;
    private $stripeService;
    private $logger;
    private $offreRepository;
    private $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        LoggerInterface $logger,
        OffreRepository $offreRepository,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->stripeService = $stripeService;
        $this->logger = $logger;
        $this->offreRepository = $offreRepository;
        $this->emailService = $emailService;
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, Request $request): Response
    {
        // Récupérer toutes les réservations
        $reservations = $reservationRepository->findAll();

        // Marquer la dernière réservation comme confirmée et envoyer l'email
        $lastReservation = end($reservations);
        if ($lastReservation && $lastReservation->getStatut() !== 'confirmé') {
            try {
                $lastReservation->setStatut('confirmé');
                $this->entityManager->persist($lastReservation);
                $this->entityManager->flush();
                
                // Envoyer l'email de confirmation
                $this->emailService->sendReservationConfirmationEmail($lastReservation);
                $this->addFlash('success', 'Votre réservation a été confirmée et un email de confirmation vous a été envoyé.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
                $this->addFlash('warning', 'La réservation est confirmée mais l\'email n\'a pas pu être envoyé.');
            }
        }

        return $this->render('FrontOffice/Reservation/index.html.twig', [
            'reservations' => $reservations,
            'title' => 'Mes réservations'
        ]);
    }

    #[Route('/new/{id}', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $id): Response
    {
        $offre = $this->offreRepository->find($id);
        
        if (!$offre) {
            throw new NotFoundHttpException('Offre non trouvée');
        }

        $reservation = new Reservation();
        $reservation->setOffre($offre);
        $reservation->setDateReservation(new \DateTime());
        $reservation->setModePaiement('carte');
        $reservation->setStatut('en_attente');

        $form = $this->createForm(ReservationType::class, $reservation, [
            'places_disponibles' => $offre->getPlacesDisponibles()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($reservation);
                $this->entityManager->flush();
                
                return $this->redirectToRoute('app_reservation_payment', [
                    'id' => $reservation->getId()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la création de la réservation: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la réservation.');
            }
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->logger->error('Form error: ' . $error->getMessage());
            }
        }

        return $this->render('FrontOffice/Reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
            'offre' => $offre
        ]);
    }

    #[Route('/{id}/show', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('FrontOffice/Reservation/show.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été modifiée avec succès !');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('FrontOffice/Reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(Reservation $reservation): Response
    {
        return $this->render('FrontOffice/Reservation/delete.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $offre = $reservation->getOffre();
            $offre->setPlacesDisponibles($offre->getPlacesDisponibles() + $reservation->getNombrePersonne());
            
            $entityManager->remove($reservation);
            $entityManager->flush();
            
            $this->addFlash('success', 'La réservation a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/payment', name: 'app_reservation_payment', methods: ['GET'])]
    public function payment(Reservation $reservation): Response
    {
        if ($reservation->getStatut() === 'payé') {
            $this->addFlash('info', 'Cette réservation a déjà été payée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('FrontOffice/Reservation/payment.html.twig', [
            'reservation' => $reservation,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY']
        ]);
    }

    #[Route('/{id}/process-payment', name: 'app_reservation_process_payment', methods: ['POST'])]
    public function processPayment(Request $request, Reservation $reservation): Response
    {
        if ($reservation->getStatut() === 'payé') {
            return $this->json(['error' => 'Cette réservation a déjà été payée.'], 400);
        }

        try {
            $amount = $reservation->getNombrePersonne() * $reservation->getOffre()->getPrix();
            $paymentIntent = $this->stripeService->createPaymentIntent($amount);
            
            return $this->json([
                'clientSecret' => $paymentIntent['clientSecret']
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur de paiement: ' . $e->getMessage());
            return $this->json(['error' => 'Une erreur est survenue lors du traitement du paiement.'], 400);
        }
    }

    #[Route('/{id}/confirm-payment', name: 'app_reservation_confirm_payment', methods: ['POST'])]
    public function confirmPayment(Request $request, Reservation $reservation): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $paymentIntentId = $data['paymentIntentId'] ?? null;

            if (!$paymentIntentId) {
                throw new \Exception('Payment Intent ID manquant');
            }

            $reservation->setStatut('payé');
            $reservation->setDatePaiement(new \DateTime());
            $reservation->setStripePaymentId($paymentIntentId);

            $offre = $reservation->getOffre();
            $offre->setPlacesDisponibles($offre->getPlacesDisponibles() - $reservation->getNombrePersonne());

            $this->entityManager->flush();

            // Rediriger vers la page de confirmation
            return $this->json([
                'success' => true,
                'message' => 'Paiement confirmé avec succès',
                'redirectUrl' => $this->generateUrl('app_reservation_confirmation', ['id' => $reservation->getId()])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la confirmation du paiement: ' . $e->getMessage());
            return $this->json([
                'error' => 'Une erreur est survenue lors de la confirmation du paiement.'
            ], 400);
        }
    }

    #[Route('/{id}/confirmation', name: 'app_reservation_confirmation')]
    public function confirmation(Reservation $reservation): Response
    {
        // Envoyer l'email de confirmation
        try {
            $this->emailService->sendReservationConfirmationEmail($reservation);
            $this->addFlash('success', 'Un email de confirmation vous a été envoyé.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de confirmation: ' . $e->getMessage());
            $this->addFlash('warning', 'La réservation est confirmée mais l\'email n\'a pas pu être envoyé.');
        }

        return $this->render('FrontOffice/Reservation/confirmation.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/admin/reservation', name: 'admin_reservation_index', methods: ['GET'])]
    public function adminIndex(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findAll();
        
        $totalPersonnes = array_reduce($reservations, function($total, $reservation) {
            return $total + $reservation->getNombrePersonne();
        }, 0);

        // Get unique offers
        $uniqueOffres = [];
        foreach ($reservations as $reservation) {
            $offre = $reservation->getOffre();
            $uniqueOffres[$offre->getId()] = $offre;
        }
        
        return $this->render('BackOffice/Reservation/index.html.twig', [
            'reservations' => $reservations,
            'total_personnes' => $totalPersonnes,
            'uniqueOffres' => array_values($uniqueOffres)
        ]);
    }

    #[Route('/admin/reservation/{id}/edit', name: 'admin_reservation_edit', methods: ['GET','POST'])]
    public function adminEdit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été mise à jour avec succès.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        return $this->render('BackOffice/Reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/admin/reservation/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]

    public function adminDelete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager)
    {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été supprimée avec succès.');

        return $this->redirectToRoute('admin_reservation_index');
    }

    #[Route('/{id}/facture', name: 'app_reservation_facture', methods: ['GET'])]
    public function facture(Reservation $reservation, Pdf $knpSnappyPdf): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $logoPath = $projectDir . '/public/images/wonderwiselogo.png';
        
        $html = $this->renderView('FrontOffice/Reservation/facture_pdf.html.twig', [
            'reservation' => $reservation,
            'logoPath' => $logoPath
        ]);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html, [
                'enable-local-file-access' => true
            ]),
            'facture_' . $reservation->getId() . '.pdf'
        );
    }

    #[Route('/admin/reservation/search', name: 'admin_reservation_search')]
    public function search(Request $request, ReservationRepository $reservationRepository): JsonResponse
    {
        $query = $request->query->get('q');
        
        $reservations = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.offre', 'o')
            ->where('r.nom LIKE :query')
            ->orWhere('r.prenom LIKE :query')
            ->orWhere('r.email LIKE :query')
            ->orWhere('o.titre LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($reservations as $reservation) {
            $results[] = [
                'id' => $reservation->getId(),
                'nom' => $reservation->getNom(),
                'prenom' => $reservation->getPrenom(),
                'email' => $reservation->getEmail(),
                'telephone' => $reservation->getTelephone(),
                'offre' => [
                    'titre' => $reservation->getOffre()->getTitre(),
                    'prix' => $reservation->getOffre()->getPrix()
                ],
                'dateDepart' => $reservation->getDateDepart() ? $reservation->getDateDepart()->format('Y-m-d') : null,
                'nombrePersonne' => $reservation->getNombrePersonne()
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/search', name: 'admin_reservation_search', methods: ['GET'])]
    public function searchReservation(Request $request, ReservationRepository $reservationRepository): JsonResponse
    {
        $search = $request->query->get('search');
        $reservations = $reservationRepository->searchReservations($search);

        $results = [];
        foreach ($reservations as $reservation) {
            $results[] = [
                'id' => $reservation->getId(),
                'client' => [
                    'nom' => $reservation->getClient()->getNom(),
                    'email' => $reservation->getClient()->getEmail()
                ],
                'offre' => [
                    'titre' => $reservation->getOffre()->getTitre(),
                    'prix' => $reservation->getOffre()->getPrix() . '€ par personne'
                ],
                'date_depart' => $reservation->getDateDepart() ? $reservation->getDateDepart()->format('Y-m-d') : 'Non définie',
                'personnes' => $reservation->getPersonnes(),
                'contact' => $reservation->getContact()
            ];
        }

        return new JsonResponse($results);
    }
}
