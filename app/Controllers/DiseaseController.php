<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DiseaseService;
use App\Services\SavedDiseaseService;



class DiseaseController extends Controller
{
    private const DEFAULT_LANGUAGE = 'en';
    public function __construct(
        private DiseaseService $service,
        private SavedDiseaseService $savedDiseaseService
    ) {
    }

    public function index(): void
    {
        $language = config('locale');

        $bodySlug = trim($_GET['body'] ?? '');

        $featured =
            (int) ($_GET['featured'] ?? 0) === 1;

        $recent =
            (int) ($_GET['recent'] ?? 0) === 1;

        $bodySystem = null;

        if ($featured) {

            $diseases = $this->service
                ->getFeaturedCatalog($language);

            $title = 'Featured Diseases';

        } elseif ($bodySlug !== '') {

            $result = $this->service
                ->getByBodySystem(
                    $bodySlug,
                    $language
                );

            $bodySystem = $result['bodySystem'];

            $diseases = $result['diseases'];

            $title = $bodySystem
                ? $bodySystem['name'] . ' Diseases'
                : 'Diseases';

        } elseif ($recent) {

            $diseases = $this->service
                ->getRecentlyViewed($language);

            $title = 'Recently Viewed Diseases';
        } else {

            $diseases = $this->service
                ->getAll($language);

            $title = 'Diseases';
        }

        $bodySystems = $this->service->getBodySystems();

        $statistics = $this->service
            ->getCatalogStatistics();

        $this->view(
            'disease/index',
            [
                'title'      => 'Diseases',
                'bodySystem' => $bodySystem,
                'bodySystems' => $bodySystems,
                'diseases'   => $diseases,
                'statistics' => $statistics
            ]
        );
    }

    /**
     * Search diseases for AJAX autocomplete.
     */
    public function search(): void
    {
        $keyword = trim($_GET['q'] ?? '');

        if ($keyword === '') {

            header('Content-Type: application/json');

            echo json_encode([]);

            return;
        }

        $results = $this->service->searchDiseases(
            $keyword,
            self::DEFAULT_LANGUAGE
        );

        header('Content-Type: application/json');

        echo json_encode($results);
    }

    public function show(
        string $slug
    ): void
    {
        $knowledge = $this->service
            ->getKnowledgeBySlug(
                $slug,
                self::DEFAULT_LANGUAGE
            );

        if (!$knowledge)
        {
            abort(404);
        }

        $relatedDiseases = $this->service
        ->getRelatedDiseases(
            $knowledge
        );

        $this->service->rememberDisease(
            (int) $knowledge['disease']['disease_id']
        );

        $isSaved = $this->savedDiseaseService->isSaved(
            (int) $knowledge['disease']['disease_id']
        );

        $this->view(
            'disease/show',
            [
                'title' => $knowledge['disease']['disease_en'],
                'knowledge' => $knowledge,
                'relatedDiseases' => $relatedDiseases,
                'isSaved' => $isSaved
            ]
        );
    }

    private function buildCatalogFilters(): array
    {
        return [

            'q' => trim($_GET['q'] ?? ''),

            'body' => trim($_GET['body'] ?? ''),

            'featured' => !empty($_GET['featured']),

            'recent' => !empty($_GET['recent']),

            'gender' => trim($_GET['gender'] ?? ''),

            'sort' => trim($_GET['sort'] ?? 'name'),

        ];
    }
}