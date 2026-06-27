<?php

declare(strict_types=1);

namespace App\Repositories;

class DiseaseRepository extends BaseRepository
{
    /**
     * Get all diseases ordered alphabetically.
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            ORDER BY disease_en
        ";

        return $this->fetchAll($sql);
    }

    /**
     * Find disease by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id
        ]);
    }

    /**
     * Find disease by slug.
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            WHERE slug = :slug
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'slug' => $slug
        ]);
    }

    /**
     * Search diseases.
     */
    public function search(string $keyword): array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            WHERE
                disease_en LIKE :keyword
                OR disease_hi LIKE :keyword
            ORDER BY disease_en
        ";

        return $this->fetchAll($sql, [
            'keyword' => '%' . $keyword . '%'
        ]);
    }
}