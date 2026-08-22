<?php

declare(strict_types=1);

namespace App\Services;
use App\Core\Session;

use App\Repositories\DiseaseRepository;
use App\Algorithms\RelatedDiseaseMatcher;
use App\Algorithms\DiseaseComparisonBuilder;
use App\Repositories\BodyPartRepository;

class DiseaseService
{
    public function __construct(
    private DiseaseRepository $repository,
    private RelatedDiseaseMatcher $matcher,
    private DiseaseComparisonBuilder $comparisonBuilder,
    private BodyPartRepository $bodyPartRepository,
    private Session $session
) {
}

    /**
     * Return diseases for the selected language.
     */
    public function getAll(
        string $language = 'en'
    ): array
    {
        return $this->getCatalog(
            [],
            $language
        );
    }

    public function getByBodySystem(
        string $slug,
        string $language = 'en'
    ): array
    {
        $result = $this->repository
            ->findByBodySlug($slug);

        return [

            'bodySystem' => $result['bodySystem'],

            'diseases' => $this->mapDiseaseCollection(
                $result['diseases'],
                $language
            )

        ];
    }

    /**
     * Search diseases for autocomplete.
     */
    public function searchDiseases(
        string $keyword,
        string $language = 'en'
    ): array
    {
        $rows = $this->repository->searchByName(
            trim($keyword)
        );

        return array_map(

            function (array $row) use ($language): array {

                return [

                    'id' => (int) $row['id'],

                    'slug' => $row['slug'],

                    'name' => $language === 'hi'
                        ? $row['disease_hi']
                        : $row['disease_en']

                ];

            },

            $rows
        );
    }

    /**
     * Return one disease.
     */
    public function getBySlug(
        string $slug,
        string $language = 'en'
    ): ?array {

        $row = $this->repository->findBySlug($slug);

        if (!$row) {
            return null;
        }

        return [

            'id' => (int) $row['id'],

            'name' => $language === 'hi'
                ? $row['disease_hi']
                : $row['disease_en'],

            'slug' => $row['slug'],

            'gender' => $row['gender'],

            'body_part_id' => (int) $row['body_part_id'],

            'icd_code' => $row['icd_code'],

            'icd10_code' => $row['icd10_code'],

        ];
    }

    public function getKnowledgeBySlug(
        string $slug,
        string $language = 'en'
    ): ?array
    {
        return $this->repository
            ->findKnowledgeBySlug(
                $slug,
                $language
            );
    }

    public function getRelatedDiseases(
        array $knowledge,
        int $limit = 6
    ): array
    {
        if (
            empty($knowledge['disease']) ||
            empty($knowledge['disease']['id'])
        ) {
            return [];
        }

        $related = $this->repository->findRelatedDiseases(
            (int) $knowledge['disease']['id'],
            $limit
        );

        if (empty($related)) {
            return [];
        }

        $currentDisease = [

            'body_part_id' =>
                $knowledge['disease']['body_part_id'] ?? null,

            'symptoms' =>
                $knowledge['symptoms'] ?? []

        ];

        return $this->matcher->rank(
            $currentDisease,
            $related
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Repository Compatibility
    |--------------------------------------------------------------------------
    |
    | During the B.1 repository migration, this method supports both the
    | legacy repository queries and the new unified catalog query.
    | Once the migration is complete, legacy fallbacks can be removed.
    |
    */

    private function mapDisease(
        array $row,
        string $language
    ): array
    {
        return [

            'id' => (int) (  $row['disease_id'] ?? $row['id'] ?? 0),

            'name' => $language === 'hi'
                ? $row['disease_hi']
                : $row['disease_en'],

            'slug' => $row['slug'],

            'gender' => $row['gender'],
            
            'symptoms'    => $row['symptoms'] ?? '',

            'symptom_count' => (int) ($row['symptom_count'] ?? 0),

            'body_part_id' => (int) $row['body_part_id'],

            'body_system' => $language === 'hi'
                ? (
                    $row['body_name_hi']
                    ?? $row['body_system']
                    ?? ''
                )
                : (
                    $row['body_name_en']
                    ?? $row['body_system']
                    ?? ''
                ),

            'icd' => $row['icd_code']
                ?? $row['icd']
                ?? '',

            'icd10' => $row['icd10_code']
                ?? $row['icd10']
                ?? '',

        ];
    }

    /**
     * Map a collection of diseases.
     *
     * @param array $rows
     * @param string $language
     * @return array
     */
    private function mapDiseaseCollection(
        array $rows,
        string $language = 'en'
    ): array
    {
        return array_map(

            fn (array $row): array =>

                $this->mapDisease(
                    $row,
                    $language
                ),

            $rows

        );
    }

    /**
     * Build comparison model for two diseases.
     */
    public function getComparison(
        string $leftSlug,
        string $rightSlug,
        string $language = 'en'
    ): ?array
    {
        $leftSlug = trim($leftSlug);
        $rightSlug = trim($rightSlug);

        if (
            $leftSlug === '' ||
            $rightSlug === ''
        ) {
            return null;
        }

        if ($leftSlug === $rightSlug) {
            return null;
        }

        $left = $this->repository
            ->findKnowledgeBySlug(
                $leftSlug,
                $language
            );

        if (!$left) {
            return null;
        }

        $right = $this->repository
            ->findKnowledgeBySlug(
                $rightSlug,
                $language
            );

        if (!$right) {
            return null;
        }

        return $this->comparisonBuilder
            ->build(
                $left,
                $right
            );
    }

    public function getCatalogStatistics(): array
    {
        return $this->repository
            ->getCatalogStatistics();
    }


    public function getBodySystems(): array
    {
        $rows = $this->bodyPartRepository->getAll();

        return array_map(

            fn (array $row): array => [

                'id' => (int) $row['id'],

                'name' => $row['name_en'],

                'slug' => $row['slug'],

                'gender' => $row['gender'],

                'disease_count' => (int) $row['disease_count']

            ],

            $rows

        );
    }

    /**
     * Return featured diseases.
     */
    public function getFeatured(
        string $language = 'en'
    ): array
    {
        $rows = $this->repository->getFeatured();

        return array_map(
            fn(array $row) => [
                'id'   => (int) $row['id'],
                'name' => $language === 'hi'
                    ? $row['disease_hi']
                    : $row['disease_en'],
                'slug' => $row['slug']
            ],
            $rows
        );
    }

    /**
     * Return all diseases marked as featured
     * using the standard catalog structure.
     */
    public function getFeaturedCatalog(
        string $language = 'en'
    ): array
    {
        $featured = $this->repository->getFeatured();

        if (empty($featured)) {
            return [];
        }

        $all = $this->getAll($language);

        $featuredIds = array_column(
            $featured,
            'id'
        );

        return array_values(
            array_filter(
                $all,
                fn(array $disease) =>
                    in_array(
                        $disease['id'],
                        $featuredIds,
                        true
                    )
            )
        );
    }

    private const RECENT_LIMIT = 10;

    public function rememberDisease(int $diseaseId): void
    {
        $recent = $this->session->get(
            'recent_diseases',
            []
        );

        $recent = array_values(
            array_diff(
                $recent,
                [$diseaseId]
            )
        );

        array_unshift(
            $recent,
            $diseaseId
        );

        $this->session->put(
            'recent_diseases',
            array_slice(
                $recent,
                0,
                self::RECENT_LIMIT
            )
        );
    }

    public function getRecentlyViewed(
        string $language = 'en'
    ): array
    {
        $ids = $this->session->get(
            'recent_diseases',
            []
        );

        if (empty($ids)) {
            return [];
        }

        $rows = $this->repository->findByIds($ids);

        return array_map(
            fn(array $row) => $this->mapDisease(
                $row,
                $language
            ),
            $rows
        );

        
    }

    /**
     * Get Disease Catalog.
     *
     * @param array $filters
     * @param string $language
     * @return array
     */
    public function getCatalog(
        array $filters = [],
        string $language = 'en'
    ): array
    {
        /*
        |--------------------------------------------------------------------------
        | Recently Viewed
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['recent']))
        {
            return $this->getRecentlyViewed($language);
        }

        $rows = $this->repository
            ->findCatalog($filters);

        return $this->mapDiseaseCollection(
            $rows,
            $language
        );
    }
}