<?php

namespace App\Twig;

use App\Repository\CountryRepository;
use App\Repository\RatingRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CountryExtension extends AbstractExtension
{
    private $countryRepository;
    private $ratingRepository;

    public function __construct(CountryRepository $countryRepository, RatingRepository $ratingRepository)
    {
        $this->countryRepository = $countryRepository;
        $this->ratingRepository = $ratingRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_country_likes_data', [$this, 'getCountryLikesData']),
        ];
    }

    public function getCountryLikesData(): array
    {
        $countries = $this->countryRepository->findAll();
        $data = [];

        foreach ($countries as $country) {
            $likes = $this->ratingRepository->count(['country' => $country, 'isLike' => true]);
            $data[] = [
                'name' => $country->getName(),
                'likes' => $likes
            ];
        }

        // Sort by likes in descending order
        usort($data, function($a, $b) {
            return $b['likes'] - $a['likes'];
        });

        return $data;
    }
}