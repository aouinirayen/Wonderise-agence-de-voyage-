<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AirQualityService
{
    private $apiKey;
    private $client;

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['NINJA_API_KEY'];
        $this->client = HttpClient::create();
    }

    public function getAirQuality(string $city): array
    {
        try {
            $response = $this->client->request('GET', 'https://api.api-ninjas.com/v1/airquality', [
                'headers' => [
                    'X-Api-Key' => $this->apiKey,
                ],
                'query' => [
                    'city' => $city
                ]
            ]);

            $data = $response->toArray();
            
            // Format the air quality data
            return [
                'overall_aqi' => $this->calculateOverallAQI($data),
                'concentration' => $data,
                'status' => $this->getAirQualityStatus($this->calculateOverallAQI($data))
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Could not fetch air quality data: ' . $e->getMessage()
            ];
        }
    }

    private function calculateOverallAQI(array $data): float
    {
        // Calculate overall AQI based on various pollutants
        $pollutants = ['CO', 'NO2', 'O3', 'SO2', 'PM10', 'PM2.5'];
        $total = 0;
        $count = 0;

        foreach ($pollutants as $pollutant) {
            if (isset($data[$pollutant]['aqi'])) {
                $total += $data[$pollutant]['aqi'];
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 1) : 0;
    }

    private function getAirQualityStatus(float $aqi): string
    {
        if ($aqi <= 50) {
            return 'Good';
        } elseif ($aqi <= 100) {
            return 'Moderate';
        } elseif ($aqi <= 150) {
            return 'Unhealthy for Sensitive Groups';
        } elseif ($aqi <= 200) {
            return 'Unhealthy';
        } elseif ($aqi <= 300) {
            return 'Very Unhealthy';
        } else {
            return 'Hazardous';
        }
    }
}
