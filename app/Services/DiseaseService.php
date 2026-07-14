<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DiseaseRepository;
use App\Algorithms\RelatedDiseaseMatcher;
use App\Algorithms\DiseaseComparisonBuilder;

class DiseaseService
{
    public function __construct(
    private DiseaseRepository $repository,
    private RelatedDiseaseMatcher $matcher,
    private DiseaseComparisonBuilder $comparisonBuilder
) {
}

    /**
     * Return diseases for the selected language.
     */
    public function getAll(string $language = 'en'): array
    {
        $rows = $this->repository->findAll();

        return array_map(
            fn (array $row): array => $this->mapDisease(
                $row,
                $language
            ),
            $rows
        );
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

    private function mapDisease(
        array $row,
        string $language
    ): array
    {
        return [

            'id' => (int) $row['id'],

            'name' => $language === 'hi'
                ? $row['disease_hi']
                : $row['disease_en'],

            'slug' => $row['slug'],

            'gender' => $row['gender'],

            'body_part_id' => (int) $row['body_part_id'],

            'icd_code' => $row['icd_code'],

            'icd10_code' => $row['icd10_code']

        ];
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
}