<?php

namespace App\Twig\Extension;

use App\Repository\RatingRepository;
use App\Repository\CountryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CountryLikesExtension extends AbstractExtension
{
    private $ratingRepository;
    private $countryRepository;

    public function __construct(RatingRepository $ratingRepository, CountryRepository $countryRepository)
    {
        $this->ratingRepository = $ratingRepository;
        $this->countryRepository = $countryRepository;
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
        $ratings = $this->ratingRepository->findBy(['country' => $country]);
        $likes = 0;
        $dislikes = 0;

        foreach ($ratings as $rating) {
            if ($rating->isLike()) {
                $likes++;
            } else {
                $dislikes++;
            }
        }

        $data[] = [
            'name' => $country->getName(),
            'likes' => $likes,
            'dislikes' => $dislikes
        ];
    }

    return $data;
}
}
