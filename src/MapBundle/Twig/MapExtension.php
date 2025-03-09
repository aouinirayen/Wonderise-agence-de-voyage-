<?php

namespace App\MapBundle\Twig;

use App\MapBundle\Service\MapService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MapExtension extends AbstractExtension
{
    private $mapService;

    public function __construct(MapService $mapService)
    {
        $this->mapService = $mapService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('map_config', [$this, 'getMapConfig']),
            new TwigFunction('map_assets', [$this, 'renderMapAssets'], ['is_safe' => ['html']])
        ];
    }

    public function getMapConfig(float $latitude = null, float $longitude = null): array
    {
        return $this->mapService->getMapConfig($latitude, $longitude);
    }

    public function renderMapAssets(): string
    {
        return '
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        ';
    }
}
