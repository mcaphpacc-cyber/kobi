<?php

namespace App\Services;
use App\Repositories\DiscoveryRepository;

class DiscoveryDashboardBuilder
{
    public function buildDashboard(
        array $statistics,
        array $bodyParts,
        array $featuredDiseases,
        array $popularSymptoms,
        array $recentDiseases
    )
    {
        return [

            'statistics' => $statistics,

            'widgets' => [

                $this->buildBodyPartsWidget($bodyParts),

                $this->buildRecentWidget($recentDiseases),

                $this->buildFeaturedDiseasesWidget($featuredDiseases),

                $this->buildPopularSymptomsWidget($popularSymptoms),

            ]

        ];
    }

    private function buildStatistics(): array
    {
        return [

            'diseases' => '--',

            'symptoms' => '--',

            'systems' => '--',

            'compare' => 'Ready'

        ];
    }

    private function buildBodyPartsWidget(
        array $bodyParts
    ): array
    {
        $items = [];

        foreach ($bodyParts as $part) {

            $items[] = [

                'title' => $part['name_en'],

                'badge' => $part['disease_count'],

                'url' => url(
                    '/diseases?body=' . $part['slug']
                )

            ];

        }

        return $this->createWidget(

            title: 'Browse Body Parts',

            icon: 'bi bi-person-bounding-box',

            type: 'links',

            data: [

                'items' => $items,

                'footer' => url('/diseases')

            ]

        );
    }

    private function buildRecentWidget(
        array $recentDiseases
    ): array
    {
        $items = [];

        foreach ($recentDiseases as $disease) {

            $items[] = [

                'title' => $disease['name'],

                'url' => url(
                    '/disease/' . $disease['slug']
                )

            ];

        }

        return $this->createWidget(

            title: 'Recently Viewed',

            icon: 'bi bi-clock-history',

            type: 'links',

            data: [

                'items' => $items

            ]

        );
    }

    private function buildFeaturedDiseasesWidget(
        array $featuredDiseases
    ): array
    {
        $items = [];

        foreach ($featuredDiseases as $disease) {

            $items[] = [

                'title' => $disease['disease_en'],

                'url' => url(
                    '/disease/' . $disease['slug']
                )

            ];

        }

        return $this->createWidget(

            title: 'Featured Diseases',

            icon: 'bi bi-graph-up',

            type: 'links',

            data: [

                'items' => $items,

                'footer' => url('/diseases?featured=1')

            ]

        );
    }

    private function buildPopularSymptomsWidget(
        array $popularSymptoms
    ): array
    {
        $items = [];

        foreach ($popularSymptoms as $symptom) {

            $items[] = $symptom['symptom_en'];

        }

        return $this->createWidget(

            title: 'Popular Symptoms',

            icon: 'bi bi-activity',

            type: 'list',

            data: [

                'items' => $items,

                'footer' => url('/symptom-checker')

            ]

        );
    }

    private function createWidget(
        string $title,
        string $icon,
        string $type,
        array $data = []
    ): array {

        return array_merge(

            [

                'title' => $title,

                'icon' => $icon,

                'type' => $type

            ],

            $data

        );

    }

}