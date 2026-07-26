<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BodyPartRepository;
use App\Repositories\DiseaseRepository;

class BodyPartService
{
    public function __construct(
        private BodyPartRepository $bodyParts,
        private DiseaseRepository $diseases
    ) {
    }

    public function index(): array
    {
        return [

            'bodyParts' =>
                $this->bodyParts->getAll(),

            'statistics' =>
                $this->diseases->getCatalogStatistics()

        ];
    }

    public function show(
        string $slug
    ): ?array {

        $page =
            $this->diseases
                 ->findByBodySlug($slug);

        if (
            $page['bodySystem'] === null
        ) {

            return null;

        }

        return $page;

    }
}