<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\AuthService;
use App\Repositories\UserTreatmentPreferenceRepository;
use RuntimeException;

class TreatmentPreferenceService
{
    public function __construct(
        private UserTreatmentPreferenceRepository $repository,
        private AuthService $authService
    ) {
    }

    /**
     * Get the current user's saved treatment order.
     */
    public function getPreferences(): array
    {
        $userId = $this->authenticatedUserId();

        return $this->repository->findByUser(
            $userId
        );
    }

    /**
     * Get all active treatment systems.
     */
    public function getTreatmentSystems(): array
    {
        //$this->authenticatedUserId();

        return $this->repository
            ->findActiveTreatmentSystems();
    }

    /**
     * Save the user's treatment order.
     */
    public function savePreferences(
        array $orderedTreatmentSystemIds
    ): void {
        $userId = $this->authenticatedUserId();

        $orderedTreatmentSystemIds =
            array_map(
                'intval',
                $orderedTreatmentSystemIds
            );

        $orderedTreatmentSystemIds =
            array_values(
                array_unique(
                    $orderedTreatmentSystemIds
                )
            );

        $activeSystems =
            $this->repository
                ->findActiveTreatmentSystems();

        $activeIds = array_map(
            static fn(array $system): int =>
                (int) $system['id'],
            $activeSystems
        );

        sort($activeIds);

        $submittedIds =
            $orderedTreatmentSystemIds;

        sort($submittedIds);

        if ($submittedIds !== $activeIds) {

            throw new RuntimeException(
                'Invalid treatment preference order.'
            );
        }

        $this->repository->saveOrder(
            $userId,
            $orderedTreatmentSystemIds
        );
    }

    /**
     * Reset the user's treatment order.
     */
    public function resetPreferences(): void
    {
        $userId = $this->authenticatedUserId();

        $this->repository->deleteByUser(
            $userId
        );
    }

    /**
     * Get the authenticated user ID.
     */
    private function authenticatedUserId(): int
    {
        $userId = $this->authService->id();

        if (!$userId) {
            throw new RuntimeException(
                'Authentication required.'
            );
        }

        return $userId;
    }

    /**
     * Get the current user's treatment display order.
     *
     * Returns an empty array for guests, allowing
     * KOBI's default treatment order to remain unchanged.
     */
    public function getDisplayOrder(): array
    {
        if (!$this->authService->check())
        {
            return [];
        }

        $preferences =
            $this->repository->findByUser(
                $this->authService->id()
            );

        return array_map(
            static fn(array $preference): int =>
                (int) $preference['treatment_system_id'],
            $preferences
        );
    }
}