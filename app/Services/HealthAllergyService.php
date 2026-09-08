<?php

namespace App\Services;

use App\Repositories\HealthAllergyRepository;
use RuntimeException;

class HealthAllergyService
{
    private HealthAllergyRepository $repository;
    private HealthProfileService $healthProfileService;


    public function __construct(
        HealthAllergyRepository $repository,
        HealthProfileService $healthProfileService
    ) {
        $this->repository = $repository;
        $this->healthProfileService = $healthProfileService;
    }


    public function getAllergies(
        int $profileId
    ): array {
        $this->getAccessibleProfile($profileId);

        return $this->repository->findByProfileId(
            $profileId
        );
    }


    public function getAllergy(
        int $profileId,
        int $allergyId
    ): array {
        $this->getAccessibleProfile($profileId);

        $allergy =
            $this->repository->findById(
                $allergyId
            );

        if (
            $allergy === null ||
            (int) $allergy['health_profile_id'] !== $profileId
        ) {
            throw new RuntimeException(
                'Allergy not found.'
            );
        }

        return $allergy;
    }


    public function createAllergy(
        int $profileId,
        array $data
    ): int {
        $profile =
            $this->getEditableProfile($profileId);

        $allergyData =
            $this->validateAndPrepareData($data);

        $allergyData['health_profile_id'] =
            (int) $profile['id'];

        return $this->repository->create(
            $allergyData
        );
    }


    public function updateAllergy(
        int $profileId,
        int $allergyId,
        array $data
    ): bool {
        $this->getEditableProfile($profileId);

        $this->getAllergy(
            $profileId,
            $allergyId
        );

        $allergyData =
            $this->validateAndPrepareData($data);

        return $this->repository->update(
            $allergyId,
            $allergyData
        );
    }


    public function deleteAllergy(
        int $profileId,
        int $allergyId
    ): bool {
        $this->getEditableProfile($profileId);

        $this->getAllergy(
            $profileId,
            $allergyId
        );

        return $this->repository->delete(
            $allergyId
        );
    }


    private function getAccessibleProfile(
        int $profileId
    ): array {
        return $this->healthProfileService->getProfile(
            $profileId
        );
    }


    private function getEditableProfile(
        int $profileId
    ): array {
        $profile =
            $this->getAccessibleProfile($profileId);

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
                'You do not have permission to modify this health record.'
            );
        }

        return $profile;
    }


    private function validateAndPrepareData(
        array $data
    ): array {
        $allergen =
            trim(
                (string) (
                    $data['allergen'] ?? ''
                )
            );

        if ($allergen === '') {
            throw new RuntimeException(
                'Allergen is required.'
            );
        }


        $category =
            trim(
                (string) (
                    $data['category'] ?? 'other'
                )
            );

        $allowedCategories = [
            'medication',
            'food',
            'environmental',
            'other'
        ];

        if (
            !in_array(
                $category,
                $allowedCategories,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid allergy category.'
            );
        }


        $severity =
            trim(
                (string) (
                    $data['severity'] ?? ''
                )
            );

        $allowedSeverities = [
            'mild',
            'moderate',
            'severe',
            'life-threatening'
        ];

        if (
            $severity !== '' &&
            !in_array(
                $severity,
                $allowedSeverities,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid allergy severity.'
            );
        }


        $identifiedOn =
            $this->normalizeDate(
                $data['identified_on'] ?? null
            );


        return [
            'allergen' => $allergen,

            'category' => $category,

            'reaction' =>
                $this->normalizeText(
                    $data['reaction'] ?? null
                ),

            'severity' =>
                $severity === ''
                    ? null
                    : $severity,

            'identified_on' =>
                $identifiedOn,

            'notes' =>
                $this->normalizeText(
                    $data['notes'] ?? null
                )
        ];
    }


    private function normalizeText(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) (
                    $value ?? ''
                )
            );

        return $value === ''
            ? null
            : $value;
    }


    private function normalizeDate(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) (
                    $value ?? ''
                )
            );

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