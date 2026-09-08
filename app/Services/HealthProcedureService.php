<?php

namespace App\Services;

use App\Repositories\HealthProcedureRepository;
use RuntimeException;

class HealthProcedureService
{
    private HealthProcedureRepository $repository;
    private HealthProfileService $healthProfileService;

    public function __construct(
        HealthProcedureRepository $repository,
        HealthProfileService $healthProfileService
    ) {
        $this->repository = $repository;
        $this->healthProfileService = $healthProfileService;
    }


    public function getProcedures(
        int $profileId
    ): array {
        $this->getAccessibleProfile($profileId);

        return $this->repository
            ->findByProfileId($profileId);
    }


    public function getProcedure(
        int $profileId,
        int $procedureId
    ): array {
        $this->getAccessibleProfile($profileId);

        $procedure =
            $this->repository->findById(
                $procedureId
            );

        if (
            $procedure === null ||
            (int) $procedure['health_profile_id'] !== $profileId
        ) {
            throw new RuntimeException(
                'Procedure not found.'
            );
        }

        return $procedure;
    }


    public function createProcedure(
        int $profileId,
        array $data
    ): int {
        $profile =
            $this->getEditableProfile($profileId);

        $procedureData =
            $this->validateAndPrepareData(
                $data
            );

        $procedureData['health_profile_id'] =
            (int) $profile['id'];

        return $this->repository->create(
            $procedureData
        );
    }


    public function updateProcedure(
        int $profileId,
        int $procedureId,
        array $data
    ): bool {
        $this->getEditableProfile($profileId);

        $this->getProcedure(
            $profileId,
            $procedureId
        );

        $procedureData =
            $this->validateAndPrepareData(
                $data
            );

        return $this->repository->update(
            $procedureId,
            $procedureData
        );
    }


    public function deleteProcedure(
        int $profileId,
        int $procedureId
    ): bool {
        $this->getEditableProfile($profileId);

        $this->getProcedure(
            $profileId,
            $procedureId
        );

        return $this->repository->delete(
            $procedureId
        );
    }


    private function getAccessibleProfile(
        int $profileId
    ): array {
        return $this->healthProfileService
            ->getProfile($profileId);
    }


    private function getEditableProfile(
        int $profileId
    ): array {
        $profile =
            $this->getAccessibleProfile(
                $profileId
            );

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
        $procedureName =
            trim(
                (string) (
                    $data['procedure_name'] ?? ''
                )
            );

        if ($procedureName === '')
        {
            throw new RuntimeException(
                'Procedure name is required.'
            );
        }


        $procedureType =
            trim(
                (string) (
                    $data['procedure_type'] ??
                    'procedure'
                )
            );

        $allowedTypes = [
            'surgery',
            'procedure',
            'hospitalization'
        ];

        if (
            !in_array(
                $procedureType,
                $allowedTypes,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid procedure type.'
            );
        }


        $performedOn =
            $this->normalizeDate(
                $data['performed_on'] ?? null
            );


        return [
            'procedure_name' =>
                $procedureName,

            'procedure_type' =>
                $procedureType,

            'performed_on' =>
                $performedOn,

            'hospital' =>
                $this->normalizeText(
                    $data['hospital'] ?? null
                ),

            'doctor' =>
                $this->normalizeText(
                    $data['doctor'] ?? null
                ),

            'reason' =>
                $this->normalizeText(
                    $data['reason'] ?? null
                ),

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

        if ($value === '')
        {
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

        if ($date > $today)
        {
            throw new RuntimeException(
                'Medical dates cannot be in the future.'
            );
        }


        return $value;
    }
}