<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private $httpClient;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function getWeatherForLocation(string $location): array
    {
        if (empty($location)) {
            $this->logger->error('Location is empty');
            throw new \InvalidArgumentException('Location cannot be empty');
        }

        try {
            $this->logger->info('Fetching weather for location: ' . $location);
            
            // Utiliser l'API de Nominatim pour le géocodage
            $geocodingResponse = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $location,
                    'format' => 'json',
                    'limit' => 1
                ],
                'headers' => [
                    'User-Agent' => 'Wonderise/1.0'
                ]
            ]);

            $geocodingData = $geocodingResponse->toArray();
            
            if (empty($geocodingData)) {
                $this->logger->warning('Location not found: ' . $location);
                throw new \Exception('Lieu non trouvé: ' . $location);
            }

            $coords = $geocodingData[0];
            $this->logger->info('Found coordinates', ['lat' => $coords['lat'], 'lon' => $coords['lon']]);
            
            // Ensuite, obtenir la météo avec les coordonnées
            $weatherResponse = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lon'],
                    'current' => ['temperature_2m', 'relative_humidity_2m', 'apparent_temperature', 'weather_code', 'wind_speed_10m', 'wind_direction_10m'],
                    'daily' => ['temperature_2m_max', 'temperature_2m_min'],
                    'timezone' => 'auto',
                    'forecast_days' => 1
                ]
            ]);

            $weatherData = $weatherResponse->toArray();
            $current = $weatherData['current'];
            $daily = $weatherData['daily'];

            $this->logger->info('Weather data retrieved successfully', [
                'temp' => $current['temperature_2m'],
                'humidity' => $current['relative_humidity_2m']
            ]);

            return [
                'temperature' => [
                    'now' => $current['temperature_2m'],
                    'feels_like' => $current['apparent_temperature'],
                    'min' => $daily['temperature_2m_min'][0],
                    'max' => $daily['temperature_2m_max'][0]
                ],
                'humidity' => $current['relative_humidity_2m'],
                'weather' => [
                    'description' => $this->getWeatherDescription($current['weather_code']),
                    'icon' => $this->getWeatherIcon($current['weather_code'])
                ],
                'wind' => [
                    'speed' => $current['wind_speed_10m'],
                    'direction' => $current['wind_direction_10m']
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching weather data', [
                'location' => $location,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function getWeatherDescription(int $code): string
    {
        $descriptions = [
            0 => 'Ciel dégagé',
            1 => 'Principalement dégagé',
            2 => 'Partiellement nuageux',
            3 => 'Couvert',
            45 => 'Brumeux',
            48 => 'Brouillard givrant',
            51 => 'Bruine légère',
            53 => 'Bruine modérée',
            55 => 'Bruine dense',
            61 => 'Pluie légère',
            63 => 'Pluie modérée',
            65 => 'Pluie forte',
            71 => 'Neige légère',
            73 => 'Neige modérée',
            75 => 'Neige forte',
            77 => 'Grains de neige',
            80 => 'Averses légères',
            81 => 'Averses modérées',
            82 => 'Averses violentes',
            85 => 'Averses de neige légères',
            86 => 'Averses de neige fortes',
            95 => 'Orage',
            96 => 'Orage avec grêle légère',
            99 => 'Orage avec grêle forte'
        ];

        return $descriptions[$code] ?? 'Conditions inconnues';
    }

    private function getWeatherIcon(int $code): string
    {
        $icons = [
            0 => '01d', // Ciel dégagé
            1 => '01d', // Principalement dégagé
            2 => '02d', // Partiellement nuageux
            3 => '04d', // Couvert
            45 => '50d', // Brumeux
            48 => '50d', // Brouillard givrant
            51 => '09d', // Bruine légère
            53 => '09d', // Bruine modérée
            55 => '09d', // Bruine dense
            61 => '10d', // Pluie légère
            63 => '10d', // Pluie modérée
            65 => '10d', // Pluie forte
            71 => '13d', // Neige légère
            73 => '13d', // Neige modérée
            75 => '13d', // Neige forte
            77 => '13d', // Grains de neige
            80 => '09d', // Averses légères
            81 => '09d', // Averses modérées
            82 => '09d', // Averses violentes
            85 => '13d', // Averses de neige légères
            86 => '13d', // Averses de neige fortes
            95 => '11d', // Orage
            96 => '11d', // Orage avec grêle légère
            99 => '11d'  // Orage avec grêle forte
        ];

        return $icons[$code] ?? '01d';
    }
}
