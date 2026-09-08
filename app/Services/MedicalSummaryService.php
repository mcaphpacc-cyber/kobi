<?php

namespace App\Services;

use App\Repositories\HealthAllergyRepository;
use App\Repositories\HealthConditionRepository;
use App\Repositories\HealthMedicineRepository;
use App\Repositories\HealthProcedureRepository;

class MedicalSummaryService
{
    private HealthConditionRepository $conditionRepository;
    private HealthMedicineRepository $medicineRepository;
    private HealthAllergyRepository $allergyRepository;
    private HealthProcedureRepository $procedureRepository;
    private HealthProfileService $healthProfileService;


    public function __construct(
        HealthConditionRepository $conditionRepository,
        HealthMedicineRepository $medicineRepository,
        HealthAllergyRepository $allergyRepository,
        HealthProcedureRepository $procedureRepository,
        HealthProfileService $healthProfileService
    ) {
        $this->conditionRepository = $conditionRepository;
        $this->medicineRepository = $medicineRepository;
        $this->allergyRepository = $allergyRepository;
        $this->procedureRepository = $procedureRepository;
        $this->healthProfileService = $healthProfileService;
    }


    /**
     * Get the medical summary for a health profile.
     */
    public function getSummary(int $profileId): array
    {
        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $conditions =
            $this->conditionRepository
                ->findByProfile($profileId);

        $medicines =
            $this->medicineRepository
                ->findByProfileId($profileId);

        $allergies =
            $this->allergyRepository
                ->findByProfileId($profileId);

        $procedures =
            $this->procedureRepository
                ->findByProfileId($profileId);


        $activeConditions = [];
        $historicalConditions = [];

        foreach ($conditions as $condition) {

            if (
                in_array(
                    $condition['status'],
                    ['active', 'chronic'],
                    true
                )
            ) {
                $activeConditions[] = $condition;
            }
            else {
                $historicalConditions[] = $condition;
            }
        }


        $currentMedicines = [];

        foreach ($medicines as $medicine) {

            if ($medicine['status'] === 'current') {
                $currentMedicines[] = $medicine;
            }
        }


        usort(
            $activeConditions,
            function (array $a, array $b): int {
                return (int) $a['id']
                    <=> (int) $b['id'];
            }
        );


        usort(
            $historicalConditions,
            function (array $a, array $b): int {
                return (int) $b['id']
                    <=> (int) $a['id'];
            }
        );


        usort(
            $currentMedicines,
            function (array $a, array $b): int {
                return (int) $a['id']
                    <=> (int) $b['id'];
            }
        );


        usort(
            $allergies,
            function (array $a, array $b): int {
                return (int) $a['id']
                    <=> (int) $b['id'];
            }
        );


        usort(
            $procedures,
            function (array $a, array $b): int {
                $dateA =
                    $a['performed_on'] ?? '';

                $dateB =
                    $b['performed_on'] ?? '';

                if ($dateA !== $dateB) {
                    return strcmp(
                        $dateB,
                        $dateA
                    );
                }

                return (int) $b['id']
                    <=> (int) $a['id'];
            }
        );


        return [
            'profile' => $profile,

            'conditions' => [
                'active' =>
                    $activeConditions,

                'historical' =>
                    $historicalConditions
            ],

            'medicines' => [
                'current' =>
                    $currentMedicines
            ],

            'allergies' =>
                $allergies,

            'procedures' =>
                $procedures,

            'timeline_available' =>
                true
        ];
    }
}