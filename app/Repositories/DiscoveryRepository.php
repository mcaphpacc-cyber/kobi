<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\DiseaseRepository;
use App\Repositories\SymptomRepository;
use App\Repositories\BodyPartRepository;

class DiscoveryRepository
{
    public function __construct(
        private DiseaseRepository $diseases,
        private SymptomRepository $symptoms,
        private BodyPartRepository $bodyParts
    ) {
    }

    public function getStatistics(): array
    {
        return [

            'diseases' => $this->diseases->count(),

            'symptoms' => $this->symptoms->count(),

            'bodyParts' => $this->bodyParts->count(),

            'compare' => 'Ready'

        ];
    }

    public function getBodyParts(): array
    {
        return $this->bodyParts->getAll();
    }

    public function getFeaturedDiseases(
        int $limit = 8
    ): array {
        return $this->diseases->getFeatured($limit);
    }

    public function getPopularSymptoms(
        int $limit = 10
    ): array {

        return array_slice(
            $this->symptoms->getPopularSymptoms(),
            0,
            $limit
        );

    }
}