<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserSavedDiseaseRepository;
use RuntimeException;

class SavedDiseaseService
{
    public function __construct(
        private UserSavedDiseaseRepository $repository,
        private Session $session
    ) {
    }

    /**
     * Save a disease for the currently authenticated user.
     */
    public function save(int $diseaseId): void
    {
        $userId = $this->authenticatedUserId();

        if ($this->repository->isSaved(
            $userId,
            $diseaseId
        )) {
            return;
        }

        $saved = $this->repository->save(
            $userId,
            $diseaseId
        );

        if (!$saved) {
            throw new RuntimeException(
                'Unable to save this disease.'
            );
        }
    }

    /**
     * Remove a saved disease for the currently authenticated user.
     */
    public function remove(int $diseaseId): void
    {
        $userId = $this->authenticatedUserId();

        $this->repository->remove(
            $userId,
            $diseaseId
        );
    }

    /**
     * Determine whether a disease is saved
     * by the currently authenticated user.
     */
    public function isSaved(int $diseaseId): bool
    {
        $userId = $this->session->get(
            'auth_user_id'
        );

        if (!$userId) {
            return false;
        }

        return $this->repository->isSaved(
            (int) $userId,
            $diseaseId
        );
    }

    /**
     * Return the IDs of diseases saved by
     * the currently authenticated user.
     */
    public function getSavedDiseaseIds(): array
    {
        $userId = $this->authenticatedUserId();

        $rows = $this->repository
            ->findDiseaseIdsByUser($userId);

        return array_map(
            fn(array $row): int => (int) $row['disease_id'],
            $rows
        );
    }

    /**
     * Return the authenticated user's ID.
     */
    private function authenticatedUserId(): int
    {
        $userId = $this->session->get(
            'auth_user_id'
        );

        if (!$userId) {
            throw new RuntimeException(
                'You must be logged in to manage saved diseases.'
            );
        }

        return (int) $userId;
    }

    public function getSavedDiseases(): array
    {
        $userId = $this->authenticatedUserId();

        return $this->repository
            ->findSavedDiseasesByUser($userId);
    }
}