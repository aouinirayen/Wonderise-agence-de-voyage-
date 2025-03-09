<?php

namespace App\MapBundle\Service;

class MapService
{
    private string $apiKey;
    private array $defaultCenter = [48.8566, 2.3522]; // Paris par défaut

    public function __construct(string $apiKey = '')
    {
        $this->apiKey = $apiKey;
    }

    public function getMapConfig(float $latitude = null, float $longitude = null): array
    {
        return [
            'center' => [
                $latitude ?? $this->defaultCenter[0],
                $longitude ?? $this->defaultCenter[1]
            ],
            'zoom' => 13,
            'tileLayer' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '© OpenStreetMap contributors'
        ];
    }

    public function geocodeAddress(string $address): ?array
    {
        $url = sprintf(
            'https://nominatim.openstreetmap.org/search?q=%s&format=json',
            urlencode($address)
        );

        $response = @file_get_contents($url);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (empty($data)) {
            return null;
        }

        return [
            'latitude' => (float) $data[0]['lat'],
            'longitude' => (float) $data[0]['lon']
        ];
    }
}
