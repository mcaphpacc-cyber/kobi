<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DiseaseRepository;

class DiseaseService
{
    public function __construct(
        private DiseaseRepository $repository
    ) {
    }

    /**
     * Return diseases for the selected language.
     */
    public function getAll(string $language = 'en'): array
    {
        $rows = $this->repository->findAll();

        return array_map(
            function (array $row) use ($language): array {

                return array_map(

                    fn(array $row): array =>
                        $this->mapDisease(
                            $row,
                            $language
                        ),

                    $rows

                );
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

    private function mapDisease(
        array $row,
        string $language
    ): array
    {
        return $this->mapDisease(
            $row,
            $language
        );
    }
}