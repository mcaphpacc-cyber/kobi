<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DiseaseRepository;
use App\Repositories\SymptomRepository;
use App\Algorithms\SymptomMatcher;

class SymptomCheckerService
{
    public function __construct(
        private readonly SymptomRepository $symptoms,
        private readonly DiseaseRepository $diseases,
        private readonly SymptomMatcher $matcher
    ) {
    }

    public function getPopularSymptoms(): array
    {
        return $this->symptoms->getPopularSymptoms();
    }

    public function checkSymptoms(array $symptomIds): array
    {
        if (empty($symptomIds)) {
            return [];
        }

        $candidateDiseases = $this->diseases->findBySymptoms($symptomIds);

        return $this->matcher->match(
            $symptomIds,
            $candidateDiseases
        );
    }
}