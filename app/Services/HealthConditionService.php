<?php

namespace App\Services;

use App\Repositories\HealthConditionRepository;
use RuntimeException;

class HealthConditionService
{
    private HealthConditionRepository $repository;
    private HealthProfileService $healthProfileService;

    public function __construct(
        HealthConditionRepository $repository,
        HealthProfileService $healthProfileService
    ) {
        $this->repository = $repository;
        $this->healthProfileService = $healthProfileService;
    }

    /**
     * Get all conditions for an accessible health profile.
     */
    public function getConditions(int $healthProfileId): array
    {
        $this->getAccessibleProfile($healthProfileId);

        return $this->repository->findByProfile($healthProfileId);
    }

    /**
     * Get a single condition belonging to an accessible health profile.
     */
    public function getCondition(
        int $healthProfileId,
        int $conditionId
    ): ?array {
        $this->getAccessibleProfile($healthProfileId);

        return $this->repository->findById(
            $conditionId,
            $healthProfileId
        );
    }

    /**
     * Create a medical condition.
     */
    public function createCondition(
        int $healthProfileId,
        array $data
    ): int {
        $this->ensureCanEdit($healthProfileId);

        $data = $this->validateAndNormalize($data);

        $data['health_profile_id'] = $healthProfileId;

        return $this->repository->create($data);
    }

    /**
     * Update a medical condition.
     */
    public function updateCondition(
        int $healthProfileId,
        int $conditionId,
        array $data
    ): bool {
        $this->ensureCanEdit($healthProfileId);

        $condition = $this->repository->findById(
            $conditionId,
            $healthProfileId
        );

        if (!$condition) {
            throw new RuntimeException('Medical condition not found.');
        }

        $data = $this->validateAndNormalize($data);

        return $this->repository->update(
            $conditionId,
            $healthProfileId,
            $data
        );
    }

    /**
     * Delete a medical condition.
     */
    public function deleteCondition(
        int $healthProfileId,
        int $conditionId
    ): bool {
        $this->ensureCanEdit($healthProfileId);

        $condition = $this->repository->findById(
            $conditionId,
            $healthProfileId
        );

        if (!$condition) {
            throw new RuntimeException('Medical condition not found.');
        }

        return $this->repository->delete(
            $conditionId,
            $healthProfileId
        );
    }

    /**
     * Validate and normalize condition data.
     */
    private function validateAndNormalize(array $data): array
    {
        $conditionName = trim((string) ($data['condition_name'] ?? ''));

        if ($conditionName === '') {
            throw new RuntimeException(
                'Condition name is required.'
            );
        }

        $allowedStatuses = [
            'active',
            'resolved',
            'chronic',
            'historical',
        ];

        $status = strtolower(
            trim((string) ($data['status'] ?? 'active'))
        );

        if (!in_array($status, $allowedStatuses, true)) {
            throw new RuntimeException(
                'Invalid medical condition status.'
            );
        }

        $diagnosedOn = $this->normalizeDate(
            $data['diagnosed_on'] ?? null
        );

        $resolvedOn = $this->normalizeDate(
            $data['resolved_on'] ?? null
        );

        if ($resolvedOn !== null && $diagnosedOn !== null) {
            if ($resolvedOn < $diagnosedOn) {
                throw new RuntimeException(
                    'Resolved date cannot be earlier than diagnosed date.'
                );
            }
        }

        if ($status === 'resolved' && $resolvedOn === null) {
            throw new RuntimeException(
                'Resolved date is required for a resolved condition.'
            );
        }

        if ($status !== 'resolved') {
            $resolvedOn = null;
        }

        return [
            'condition_name' => $conditionName,
            'status' => $status,
            'diagnosed_on' => $diagnosedOn,
            'resolved_on' => $resolvedOn,
            'doctor_hospital' => $this->normalizeText(
                $data['doctor_hospital'] ?? null
            ),
            'notes' => $this->normalizeText(
                $data['notes'] ?? null
            ),
        ];
    }

    /**
     * Normalize optional text values.
     */
    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * Normalize date values.
     */
    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if (
            !$date ||
            $date->format('Y-m-d') !== $value
        ) {
            throw new RuntimeException(
                'Invalid date format.'
            );
        }

        $today = new \DateTime('today');

        if ($date > $today) {
            throw new RuntimeException(
                'Medical dates cannot be in the future.'
            );
        }

        return $value;
    }

    /**
     * Get an accessible health profile.
     */
    private function getAccessibleProfile(int $healthProfileId): array
    {
        $profile = $this->healthProfileService->getProfile(
            $healthProfileId
        );

        if (!$profile) {
            throw new RuntimeException(
                'Health profile not found.'
            );
        }

        return $profile;
    }

    /**
     * Ensure the authenticated user can modify the profile.
     */
    private function ensureCanEdit(int $healthProfileId): void
    {
        $profile = $this->getAccessibleProfile($healthProfileId);

        $role = $profile['role'] ?? null;

        if (!in_array($role, ['owner', 'editor'], true)) {
            throw new RuntimeException(
                'You do not have permission to modify this health profile.'
            );
        }
    }
}