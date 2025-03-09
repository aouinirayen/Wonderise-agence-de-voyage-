<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/calendar-bundle')]
class CalendarBundleController extends AbstractController
{
    private $evenementRepository;

    public function __construct(EvenementRepository $evenementRepository)
    {
        $this->evenementRepository = $evenementRepository;
    }

    #[Route('/', name: 'app_calendar_bundle')]
    public function index(): Response
    {
        return $this->render('calendar_bundle/index.html.twig');
    }

    #[Route('/events', name: 'app_calendar_events', methods: ['GET'])]
    public function getEvents(Request $request): JsonResponse
    {
        $events = $this->evenementRepository->findAll();
        $calendarEvents = [];

        foreach ($events as $event) {
            if ($event->getDate() && $event->getHeure()) {
                $date = $event->getDate();
                $time = $event->getHeure();
                
                $startDateTime = new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
                $endDateTime = clone $startDateTime;
                $endDateTime->modify('+1 hour');

                $calendarEvents[] = [
                    'id' => $event->getId(),
                    'title' => $event->getNom(),
                    'start' => $startDateTime->format('Y-m-d\TH:i:s'),
                    'end' => $endDateTime->format('Y-m-d\TH:i:s'),
                    'description' => $event->getDescription(),
                    'location' => $event->getLieu(),
                    'allDay' => false
                ];
            }
        }

        return new JsonResponse($calendarEvents);
    }
}
