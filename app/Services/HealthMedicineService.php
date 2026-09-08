<?php

namespace App\Services;

use App\Repositories\HealthMedicineRepository;
use RuntimeException;

class HealthMedicineService
{
    private HealthMedicineRepository $repository;

    private HealthProfileService $healthProfileService;


    public function __construct(
        HealthMedicineRepository $repository,
        HealthProfileService $healthProfileService
    ) {
        $this->repository = $repository;
        $this->healthProfileService = $healthProfileService;
    }


    /**
     * Get medicines for an accessible health profile.
     */
    public function getMedicines(int $profileId): array
    {
        $this->getAccessibleProfile($profileId);

        return $this->repository->findByProfileId(
            $profileId
        );
    }


    /**
     * Get a single medicine after verifying profile access.
     */
    public function getMedicine(
        int $profileId,
        int $medicineId
    ): array {
        $this->getAccessibleProfile($profileId);

        $medicine = $this->repository->findById(
            $medicineId
        );

        if (
            $medicine === null ||
            (int) $medicine['health_profile_id'] !== $profileId
        ) {
            throw new RuntimeException(
                'Medicine not found.'
            );
        }

        return $medicine;
    }


    /**
     * Create a medicine record.
     */
    public function createMedicine(
        int $profileId,
        array $data
    ): int {
        $profile = $this->getEditableProfile(
            $profileId
        );

        $medicineData =
            $this->validateAndPrepareData($data);

        $medicineData['health_profile_id'] =
            (int) $profile['id'];

        return $this->repository->create(
            $medicineData
        );
    }


    /**
     * Update a medicine record.
     */
    public function updateMedicine(
        int $profileId,
        int $medicineId,
        array $data
    ): bool {
        $this->getEditableProfile($profileId);

        $this->getMedicine(
            $profileId,
            $medicineId
        );

        $medicineData =
            $this->validateAndPrepareData($data);

        return $this->repository->update(
            $medicineId,
            $medicineData
        );
    }


    /**
     * Delete a medicine record.
     */
    public function deleteMedicine(
        int $profileId,
        int $medicineId
    ): bool {
        $this->getEditableProfile($profileId);

        $this->getMedicine(
            $profileId,
            $medicineId
        );

        return $this->repository->delete(
            $medicineId
        );
    }


    /**
     * Get the currently authenticated user's ID.
     */
    private function authenticatedUserId(): int
    {
        $userId = (int) (
            $_SESSION['user_id'] ?? 0
        );

        if ($userId <= 0) {
            throw new RuntimeException(
                'Authentication required.'
            );
        }

        return $userId;
    }


    /**
     * Get an accessible profile.
     */
    private function getAccessibleProfile(
        int $profileId
    ): array {
        return $this->healthProfileService->getProfile(
            $profileId
        );
    }


    /**
     * Get a profile that the current user can edit.
     */
    private function getEditableProfile(
        int $profileId
    ): array {
        $profile =
            $this->getAccessibleProfile($profileId);

        $role = $profile['role'] ?? null;

        if (
            !in_array(
                $role,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to modify this health record.'
            );
        }

        return $profile;
    }


    /**
     * Validate and normalize medicine data.
     */
    private function validateAndPrepareData(
        array $data
    ): array {

        $brandName =
            trim((string) (
                $data['brand_name'] ?? ''
            ));

        if ($brandName === '') {
            throw new RuntimeException(
                'Brand name is required.'
            );
        }

        $status =
            trim((string) (
                $data['status'] ?? 'current'
            ));

        $allowedStatuses = [
            'current',
            'stopped',
            'historical'
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid medicine status.'
            );
        }


        $startedOn =
            $this->normalizeDate(
                $data['started_on'] ?? null
            );

        $stoppedOn =
            $this->normalizeDate(
                $data['stopped_on'] ?? null
            );


        if (
            $status === 'current' &&
            $stoppedOn !== null
        ) {
            throw new RuntimeException(
                'A current medicine cannot have a stopped date.'
            );
        }


        if (
            $status === 'stopped' &&
            $stoppedOn === null
        ) {
            throw new RuntimeException(
                'Stopped date is required for a stopped medicine.'
            );
        }


        if (
            $startedOn !== null &&
            $stoppedOn !== null &&
            $stoppedOn < $startedOn
        ) {
            throw new RuntimeException(
                'Stopped date cannot be earlier than started date.'
            );
        }


        return [
            'brand_name' =>
                $brandName,

            'generic_name' =>
                $this->normalizeText(
                    $data['generic_name'] ?? null
                ),

            'strength' =>
                $this->normalizeText(
                    $data['strength'] ?? null
                ),

            'dose' =>
                $this->normalizeText(
                    $data['dose'] ?? null
                ),

            'frequency' =>
                $this->normalizeText(
                    $data['frequency'] ?? null
                ),

            'route' =>
                $this->normalizeText(
                    $data['route'] ?? null
                ),

            'status' =>
                $status,

            'started_on' =>
                $startedOn,

            'stopped_on' =>
                $stoppedOn,

            'prescribed_by' =>
                $this->normalizeText(
                    $data['prescribed_by'] ?? null
                ),

            'notes' =>
                $this->normalizeText(
                    $data['notes'] ?? null
                )
        ];
    }


    /**
     * Normalize optional text values.
     */
    private function normalizeText(
        mixed $value
    ): ?string {
        $value =
            trim((string) ($value ?? ''));

        return $value === ''
            ? null
            : $value;
    }


    /**
     * Normalize and validate a date.
     */
    private function normalizeDate(
        mixed $value
    ): ?string {

        $value =
            trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $date =
            \DateTime::createFromFormat(
                'Y-m-d',
                $value
            );

        if (
            !$date ||
            $date->format('Y-m-d') !== $value
        ) {
            throw new RuntimeException(
                'Invalid date format.'
            );
        }

        $today =
            new \DateTime('today');

        if ($date > $today) {
            throw new RuntimeException(
                'Medical dates cannot be in the future.'
            );
        }

        return $value;
    }
}