<?php

namespace App\WeatherBundle\Controller;

use App\WeatherBundle\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WeatherController extends AbstractController
{
    private $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    #[Route('/weather/{country}', name: 'weather_show')]
    public function show(string $country): Response
    {
        $weather = $this->weatherService->getWeatherForCountry($country);

        return $this->render('@Weather/weather.html.twig', [
            'weather' => $weather
        ]);
    }
}
