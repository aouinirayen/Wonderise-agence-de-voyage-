<?php

namespace App\WeatherBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private $httpClient;
    private $apiKey;
    private $logger;

    private $cityTranslations = [
        'Londres' => 'London,GB',
        'Paris' => 'Paris,FR',
        'Rome' => 'Roma,IT',
        'Madrid' => 'Madrid,ES',
        'Berlin' => 'Berlin,DE',
        'Barcelone' => 'Barcelona,ES',
        'Amsterdam' => 'Amsterdam,NL',
        'Venise' => 'Venice,IT',
        'Florence' => 'Florence,IT',
        'Milan' => 'Milan,IT',
        'Lisbonne' => 'Lisbon,PT',
        'Marrakech' => 'Marrakech,MA',
        'Istanbul' => 'Istanbul,TR',
        'Athènes' => 'Athens,GR',
        'Nice' => 'Nice,FR',
        'Marseille' => 'Marseille,FR',
        'Lyon' => 'Lyon,FR',
        'Séville' => 'Seville,ES',
        'Valence' => 'Valencia,ES',
        'Munich' => 'Munich,DE',
        'Porto' => 'Porto,PT',
        'Dubai' => 'Dubai,AE',
        'Dubaï' => 'Dubai,AE', 
        'Moscou' => 'Moscow,RU',
        'Saint-Pétersbourg' => 'Saint Petersburg,RU',
        'Sotchi' => 'Sochi,RU',
        'Kazan' => 'Kazan,RU',
        'Ekaterinbourg' => 'Yekaterinburg,RU',
        'Novossibirsk' => 'Novosibirsk,RU'
    ];

    public function __construct(HttpClientInterface $httpClient, string $apiKey, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function getWeatherForCity(string $city): array
    {
        try {
            // Si la ville n'est pas dans notre liste de traductions, on essaie de la rechercher directement
            if (!isset($this->cityTranslations[$city])) {
                $this->logger->warning('City not found in translations: ' . $city);
                // On recherche la ville sans code pays
                $cityName = $city;
            } else {
                $cityName = $this->cityTranslations[$city];
            }
            
            $this->logger->info('Fetching weather for city: ' . $cityName);

            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $cityName,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr'
                ]
            ]);

            $data = $response->toArray();
            
            $this->logger->info('Weather API response:', ['data' => $data]);

            if (isset($data['main'], $data['weather'][0])) {
                $result = [
                    'success' => true,
                    'temperature' => $data['main']['temp'],
                    'description' => $data['weather'][0]['description'],
                    'icon' => $this->getWeatherIcon($data['weather'][0]['icon']),
                    'humidity' => $data['main']['humidity'],
                    'wind' => round($data['wind']['speed'] * 3.6), // Conversion de m/s en km/h
                    'location' => $city,
                    'raw_temp' => $data['main']['temp'] // Température non arrondie pour debug
                ];
                
                $this->logger->info('Processed weather data:', ['result' => $result]);
                
                return $result;
            }

            $this->logger->error('Invalid weather data structure');
            return [
                'success' => false,
                'error' => 'Données météo non disponibles'
            ];
        } catch (\Exception $e) {
            $this->logger->error('Weather API error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des données météo: ' . $e->getMessage()
            ];
        }
    }

    private function getWeatherIcon(string $iconCode): string
    {
        $iconMapping = [
            '01d' => 'sun', // Ciel dégagé (jour)
            '01n' => 'moon', // Ciel dégagé (nuit)
            '02d' => 'cloud-sun', // Quelques nuages (jour)
            '02n' => 'cloud-moon', // Quelques nuages (nuit)
            '03d' => 'cloud', // Nuages épars
            '03n' => 'cloud',
            '04d' => 'cloud', // Nuages
            '04n' => 'cloud',
            '09d' => 'cloud-showers-heavy', // Averses
            '09n' => 'cloud-showers-heavy',
            '10d' => 'cloud-sun-rain', // Pluie (jour)
            '10n' => 'cloud-moon-rain', // Pluie (nuit)
            '11d' => 'bolt', // Orage
            '11n' => 'bolt',
            '13d' => 'snowflake', // Neige
            '13n' => 'snowflake',
            '50d' => 'smog', // Brume
            '50n' => 'smog'
        ];

        return $iconMapping[$iconCode] ?? 'cloud';
    }
}
