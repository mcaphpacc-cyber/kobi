<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SubstanceRepository;

class SubstanceService
{
    private SubstanceRepository $repository;

    public function __construct(
        SubstanceRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Search substances for autocomplete suggestions.
     */
    public function search(
        string $term,
        int $limit = 10
    ): array {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        return $this->repository->search(
            $term,
            $limit
        );
    }
}