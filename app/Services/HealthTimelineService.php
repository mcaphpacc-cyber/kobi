<?php

namespace App\Services;

use App\Repositories\HealthAllergyRepository;
use App\Repositories\HealthConditionRepository;
use App\Repositories\HealthMedicineRepository;
use App\Repositories\HealthProcedureRepository;
use App\Repositories\HealthDocumentRepository;

class HealthTimelineService
{
    private HealthConditionRepository $conditionRepository;
    private HealthMedicineRepository $medicineRepository;
    private HealthAllergyRepository $allergyRepository;
    private HealthProcedureRepository $procedureRepository;
    private HealthProfileService $healthProfileService;
    private HealthDocumentRepository $documentRepository;


    public function __construct(
        HealthConditionRepository $conditionRepository,
        HealthMedicineRepository $medicineRepository,
        HealthAllergyRepository $allergyRepository,
        HealthProcedureRepository $procedureRepository,
        HealthProfileService $healthProfileService,
        HealthDocumentRepository $documentRepository
    ) {
        $this->conditionRepository = $conditionRepository;
        $this->medicineRepository = $medicineRepository;
        $this->allergyRepository = $allergyRepository;
        $this->procedureRepository = $procedureRepository;
        $this->healthProfileService = $healthProfileService;
        $this->documentRepository = $documentRepository;
    }


    /**
     * Get the complete medical timeline for a health profile.
     */
    public function getTimeline(int $profileId): array
    {
        $this->healthProfileService->getProfile($profileId);

        $events = [];

        $this->addConditionEvents(
            $events,
            $this->conditionRepository->findByProfile($profileId)
        );

        $this->addMedicineEvents(
            $events,
            $this->medicineRepository->findByProfileId($profileId)
        );

        $this->addAllergyEvents(
            $events,
            $this->allergyRepository->findByProfileId($profileId)
        );

        $this->addProcedureEvents(
            $events,
            $this->procedureRepository->findByProfileId($profileId)
        );

        $this->addDocumentEvents(
            $events,
            $this->documentRepository->findByProfileId($profileId)
        );

        usort(
            $events,
            function (array $a, array $b): int {
                $dateComparison =
                    strcmp(
                        $b['event_date'],
                        $a['event_date']
                    );

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                return $b['sort_order'] <=> $a['sort_order'];
            }
        );

        foreach ($events as &$event) {
            unset($event['sort_order']);
        }

        unset($event);

        return $events;
    }


    /**
     * Add condition events to the timeline.
     */
    private function addConditionEvents(
        array &$events,
        array $conditions
    ): void {
        foreach ($conditions as $condition) {

            if (!empty($condition['diagnosed_on'])) {

                $events[] = [
                    'event_type' => 'condition',
                    'event_date' => $condition['diagnosed_on'],
                    'title' => $condition['condition_name'],
                    'subtitle' => 'Condition Diagnosed',
                    'source_type' => 'condition',
                    'source_id' => (int) $condition['id'],
                    'metadata' => [
                        'status' => $condition['status'],
                        'doctor_hospital' =>
                            $condition['doctor_hospital']
                    ],
                    'sort_order' => (int) $condition['id']
                ];
            }


            if (!empty($condition['resolved_on'])) {

                $events[] = [
                    'event_type' => 'condition',
                    'event_date' => $condition['resolved_on'],
                    'title' => $condition['condition_name'],
                    'subtitle' => 'Condition Resolved',
                    'source_type' => 'condition',
                    'source_id' => (int) $condition['id'],
                    'metadata' => [
                        'status' => $condition['status'],
                        'doctor_hospital' =>
                            $condition['doctor_hospital']
                    ],
                    'sort_order' => (int) $condition['id']
                ];
            }
        }
    }


    /**
     * Add medicine events to the timeline.
     */
    private function addMedicineEvents(
        array &$events,
        array $medicines
    ): void {
        foreach ($medicines as $medicine) {

            if (!empty($medicine['started_on'])) {

                $events[] = [
                    'event_type' => 'medicine',
                    'event_date' => $medicine['started_on'],
                    'title' => $this->medicineTitle($medicine),
                    'subtitle' => 'Medicine Started',
                    'source_type' => 'medicine',
                    'source_id' => (int) $medicine['id'],
                    'metadata' => [
                        'brand_name' =>
                            $medicine['brand_name'],

                        'generic_name' =>
                            $medicine['generic_name'],

                        'strength' =>
                            $medicine['strength'],

                        'dose' =>
                            $medicine['dose'],

                        'frequency' =>
                            $medicine['frequency'],

                        'status' =>
                            $medicine['status']
                    ],
                    'sort_order' => (int) $medicine['id']
                ];
            }


            if (
                $medicine['status'] === 'stopped' &&
                !empty($medicine['stopped_on'])
            ) {

                $events[] = [
                    'event_type' => 'medicine',
                    'event_date' => $medicine['stopped_on'],
                    'title' => $this->medicineTitle($medicine),
                    'subtitle' => 'Medicine Stopped',
                    'source_type' => 'medicine',
                    'source_id' => (int) $medicine['id'],
                    'metadata' => [
                        'brand_name' =>
                            $medicine['brand_name'],

                        'generic_name' =>
                            $medicine['generic_name'],

                        'strength' =>
                            $medicine['strength'],

                        'dose' =>
                            $medicine['dose'],

                        'frequency' =>
                            $medicine['frequency'],

                        'status' =>
                            $medicine['status']
                    ],
                    'sort_order' => (int) $medicine['id']
                ];
            }
        }
    }


    /**
     * Add allergy events to the timeline.
     */
    private function addAllergyEvents(
        array &$events,
        array $allergies
    ): void {
        foreach ($allergies as $allergy) {

            if (empty($allergy['identified_on'])) {
                continue;
            }

            $events[] = [
                'event_type' => 'allergy',
                'event_date' => $allergy['identified_on'],
                'title' => $allergy['allergen'],
                'subtitle' => 'Allergy Identified',
                'source_type' => 'allergy',
                'source_id' => (int) $allergy['id'],
                'metadata' => [
                    'category' =>
                        $allergy['category'],

                    'reaction' =>
                        $allergy['reaction'],

                    'severity' =>
                        $allergy['severity']
                ],
                'sort_order' => (int) $allergy['id']
            ];
        }
    }


    /**
     * Add procedure events to the timeline.
     */
    private function addProcedureEvents(
        array &$events,
        array $procedures
    ): void {
        foreach ($procedures as $procedure) {

            if (empty($procedure['performed_on'])) {
                continue;
            }

            $typeLabels = [
                'surgery' => 'Surgery',
                'procedure' => 'Procedure',
                'hospitalization' => 'Hospitalization'
            ];

            $typeLabel =
                $typeLabels[
                    $procedure['procedure_type']
                ]
                ?? 'Procedure';

            $events[] = [
                'event_type' => 'procedure',
                'event_date' => $procedure['performed_on'],
                'title' => $procedure['procedure_name'],
                'subtitle' => $typeLabel,
                'source_type' => 'procedure',
                'source_id' => (int) $procedure['id'],
                'metadata' => [
                    'hospital' =>
                        $procedure['hospital'],

                    'doctor' =>
                        $procedure['doctor'],

                    'reason' =>
                        $procedure['reason']
                ],
                'sort_order' => (int) $procedure['id']
            ];
        }
    }

    /**
     * Add document events to the timeline.
     */
    private function addDocumentEvents(
        array &$events,
        array $documents
    ): void {
        $typeLabels = [
            'lab_report' => 'Lab Report',
            'imaging' => 'Imaging',
            'prescription' => 'Prescription',
            'discharge_summary' => 'Discharge Summary',
            'medical_record' => 'Medical Record',
            'surgery_procedure' => 'Surgery / Procedure',
            'consultation' => 'Consultation',
            'other' => 'Other'
        ];

        foreach ($documents as $document) {

            if (empty($document['document_date'])) {
                continue;
            }

            $documentType =
                (string) (
                    $document['document_type'] ?? 'other'
                );

            $typeLabel =
                $typeLabels[$documentType]
                ?? 'Other';

            $events[] = [
                'event_type' => 'document',
                'event_date' => $document['document_date'],
                'title' => $document['title'],
                'subtitle' => $typeLabel,
                'source_type' => 'document',
                'source_id' => (int) $document['id'],
                'metadata' => [
                    'document_type' => $documentType,
                    'hospital' =>
                        $document['hospital'] ?? '',
                    'doctor' =>
                        $document['doctor'] ?? ''
                ],
                'sort_order' => (int) $document['id']
            ];
        }
    }


    /**
     * Build a readable medicine title.
     */
    private function medicineTitle(array $medicine): string
    {
        $title = trim(
            (string) ($medicine['brand_name'] ?? '')
        );

        if (
            $title === '' &&
            !empty($medicine['generic_name'])
        ) {
            $title = trim(
                (string) $medicine['generic_name']
            );
        }

        if (!empty($medicine['strength'])) {

            $title .= ' ' .
                trim(
                    (string) $medicine['strength']
                );
        }

        return $title;
    }
}