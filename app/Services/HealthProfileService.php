<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HealthProfileRepository;
use RuntimeException;

class HealthProfileService
{
    public function __construct(
        private HealthProfileRepository $repository,
        private AuthService $authService
    ) {
    }

    /**
     * Get all health profiles accessible
     * to the authenticated user.
     */
    public function getAccessibleProfiles(): array
    {
        $userId = $this->authenticatedUserId();

        return $this->repository
            ->findAccessibleByUser($userId);
    }

    /**
     * Get a single accessible health profile.
     */
    public function getProfile(
        int $profileId
    ): array {
        $userId = $this->authenticatedUserId();

        $profile =
            $this->repository->findAccessible(
                $profileId,
                $userId
            );

        if ($profile === null)
        {
            throw new RuntimeException(
                'Health profile not found.'
            );
        }

        return $profile;
    }

    /**
     * Create a family health profile
     * and make the authenticated user its owner.
     */
    public function createFamilyProfile(
        string $fullName,
        ?string $dateOfBirth,
        string $gender
    ): int {
        $userId = $this->authenticatedUserId();

        $fullName = trim($fullName);

        if ($fullName === '')
        {
            throw new RuntimeException(
                'Name is required.'
            );
        }

        $this->validateGender($gender);

        return $this->repository->createProfileWithOwner(
            'family',
            $fullName,
            $dateOfBirth,
            $gender,
            $userId
        );
    }

    /**
     * Get the authenticated user's ID.
     */
    private function authenticatedUserId(): int
    {
        $userId =
            $this->authService->id();

        if (!$userId)
        {
            throw new RuntimeException(
                'Authentication required.'
            );
        }

        return $userId;
    }

    /**
     * Validate profile gender.
     */
    private function validateGender(
        string $gender
    ): void {
        $allowed = [
            'male',
            'female',
            'other',
            'unspecified'
        ];

        if (!in_array(
            $gender,
            $allowed,
            true
        ))
        {
            throw new RuntimeException(
                'Invalid gender.'
            );
        }
    }

    /**
     * Ensure the authenticated user has
     * an active self health profile.
     *
     * Returns the existing or newly created profile ID.
     */
    public function ensureSelfProfile(): int
    {
        $userId = $this->authenticatedUserId();

        $existing =
            $this->repository->findSelfProfile($userId);

        if ($existing !== null)
        {
            return (int) $existing['id'];
        }

        $user =
            $this->authService->user();

        if ($user === null)
        {
            throw new RuntimeException(
                'Unable to retrieve authenticated user.'
            );
        }

        $name =
            trim((string) ($user['name'] ?? ''));

        if ($name === '')
        {
            throw new RuntimeException(
                'Unable to create your health profile.'
            );
        }

        return $this->repository->createProfileWithOwner(
            'self',
            $name,
            null,
            'unspecified',
            $userId
        );
    }

    public function updateProfile(
        int $profileId,
        string $fullName,
        ?string $dateOfBirth,
        string $gender
    ): void {
        $userId = $this->authenticatedUserId();

        $fullName = trim($fullName);

        if ($fullName === '')
        {
            throw new RuntimeException(
                'Full name is required.'
            );
        }

        $this->validateGender($gender);

        $profile =
            $this->repository->findAccessible(
                $profileId,
                $userId
            );

        if ($profile === null)
        {
            throw new RuntimeException(
                'Health profile not found.'
            );
        }

        $role =
            $profile['role'] ?? null;

        if (
            !in_array(
                $role,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to edit this health profile.'
            );
        }

        $updated =
            $this->repository->updateProfile(
                $profileId,
                $userId,
                $fullName,
                $dateOfBirth,
                $gender
            );

        if (!$updated)
        {
            throw new RuntimeException(
                'Unable to update the health profile.'
            );
        }
    }
}