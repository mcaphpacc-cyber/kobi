<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DiscoveryRepository;

class DiscoveryService
{
    public function __construct(
    DiscoveryRepository $repository,
    DiscoveryDashboardBuilder $builder
    ) {
        $this->repository = $repository;
        $this->builder = $builder;
    }

    public function getDashboard(): array
    {
        return $this->builder->buildDashboard(

            statistics: $this->repository->getStatistics(),

            bodyParts: array_slice(
                $this->repository->getBodyParts(),
                0,
                8
            ),

            featuredDiseases:
                $this->repository->getFeaturedDiseases(),

            popularSymptoms:
                $this->repository->getPopularSymptoms(8)

        );
    }
}