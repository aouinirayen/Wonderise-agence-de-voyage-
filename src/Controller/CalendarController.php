<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Calendar\Calendar;
use Calendar\Event;
use Symfony\Component\HttpFoundation\Request;

#[Route('/evenement')]
class CalendarController extends AbstractController
{
    private $evenementRepository;

    public function __construct(EvenementRepository $evenementRepository)
    {
        $this->evenementRepository = $evenementRepository;
    }

    #[Route('/calendar', name: 'app_calendar')]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/calendar/events', name: 'fc_load_events')]
    public function getEvents(Request $request): Response
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

        return $this->json($calendarEvents);
    }
}
