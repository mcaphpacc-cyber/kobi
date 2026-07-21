<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BodyPartRepository;
use App\Repositories\CauseRepository;
use App\Repositories\DiseaseRepository;
use App\Repositories\SymptomRepository;
use App\Repositories\HomeRepository;


class HomeService
{
    public function __construct(
        private DiseaseRepository $diseases,
        private BodyPartRepository $bodyParts,
        private SymptomRepository $symptoms,
        private HomeRepository $home,
        private CauseRepository $causes
    ) {
    }

    public function getDashboardData(): array
    {
        return [

            'statistics' => [

                'diseases'  => $this->diseases->count(),

                'bodyParts' => $this->bodyParts->count(),

                'symptoms'  => $this->symptoms->count(),

                'causes'    => $this->causes->count(),

            ],

            'bodyParts' => array_map(
                fn(array $row): array => [
                    'id' => (int) $row['id'],
                    'name' => config('locale') === 'hi'
                        ? $row['name_hi']
                        : $row['name_en'],
                    'slug' => $row['slug'],
                    'gender' => $row['gender'],
                ],
                array_slice(
                    $this->bodyParts->getAll(),
                    0,
                    8
                )
            ),

            'bodySystems' => array_map(

                fn(array $row): array => [

                    'id' => (int) $row['id'],

                    'name' => $row['name_en'],

                    'slug' => $row['slug'],

                    'disease_count' => (int) $row['disease_count'],

                ],

                $this->home->getBodySystems()

            ),

            'featuredDiseases' => array_map(
                fn(array $row): array => [
                    'id' => (int) $row['id'],
                    'name' => config('locale') === 'hi'
                        ? $row['disease_hi']
                        : $row['disease_en'],
                    'slug' => $row['slug'],
                ],
                $this->diseases->getFeatured()
            ),

        ];
    }

    public function searchSuggestions(
        string $keyword
    ): array
    {
        return $this->home
            ->searchDiseases($keyword);
    }
}