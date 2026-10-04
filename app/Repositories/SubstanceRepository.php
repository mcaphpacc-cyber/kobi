<?php

declare(strict_types=1);

namespace App\Repositories;

class SubstanceRepository extends BaseRepository
{
    /**
     * Search active and searchable substances by canonical name.
     *
     * Intended for autocomplete / suggestion searches.
     */
    public function search(
        string $term,
        int $limit = 10
    ): array {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $limit = max(1, min($limit, 10));

        $sql = "
            SELECT
                substance_id,
                canonical_name
            FROM kobi_substances
            WHERE
                is_active = 1
                AND is_searchable = 1
                AND canonical_name_normalized LIKE :term
            ORDER BY
                canonical_name_normalized ASC
            LIMIT {$limit}
        ";

        return $this->fetchAll(
            $sql,
            [
                'term' => $term . '%'
            ]
        );
    }
}