<?php

declare(strict_types=1);

namespace App\Repositories;

class SymptomRepository extends BaseRepository
{
    public function count(): int
    {
        return $this->countRecords('symptoms');
    }
    public function getPopularSymptoms(): array
    {
        $sql = "
            SELECT
                id,
                symptom_en,
                symptom_hi,
                slug
            FROM symptoms
            ORDER BY search_count DESC
            LIMIT 20
        ";

        return $this->fetchAll($sql);
    }

    public function search(string $keyword): array
    {
        return $this->fetchAll(
            "
            SELECT
                id,
                symptom_en,
                symptom_hi,
                slug
            FROM symptoms
            WHERE symptom_en LIKE :keyword
            ORDER BY symptom_en
            LIMIT 10
            ",
            [
                'keyword' => '%' . $keyword . '%'
            ]
        );
    }

    public function findByIds(array $ids): array
    {
        return [];
    }

    public function findBySlug(string $slug): ?array
    {
        return null;
    }

    public function incrementSearchCount(
        array $ids
    ): void
    {
        if (empty($ids)) {
            return;
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($ids), '?')
        );

        $sql = "
            UPDATE symptoms
            SET search_count = search_count + 1
            WHERE id IN ($placeholders)
        ";

        $this->execute($sql, $ids);
    }
}