<?php

declare(strict_types=1);

namespace App\Services\Disease;

use App\Repositories\DiseaseRepository;
use RuntimeException;

class DiseaseAdminService
{
    public function __construct(
        private DiseaseRepository $diseases
    ) {
    }

    public function getDisease(
        int $diseaseId
    ): ?array {
        return $this->diseases
            ->findForAdmin($diseaseId);
    }

    public function updateSeverityAndUrgency(
        int $diseaseId,
        array $data
    ): void {
        $severityLevel =
            trim((string) (
                $data['severity_level'] ?? ''
            ));

        $urgencyLevel =
            trim((string) (
                $data['urgency_level'] ?? ''
            ));

        $urgencyNoteEn =
            trim((string) (
                $data['urgency_note_en'] ?? ''
            ));

        $urgencyNoteHi =
            trim((string) (
                $data['urgency_note_hi'] ?? ''
            ));

        $validSeverity = [
            'mild',
            'moderate',
            'urgent',
            'emergency'
        ];

        $validUrgency = [
            'routine',
            'soon',
            'urgent',
            'emergency'
        ];

        if (!in_array(
            $severityLevel,
            $validSeverity,
            true
        )) {
            throw new RuntimeException(
                'Invalid severity level.'
            );
        }

        if (
            $urgencyLevel !== ''
            &&
            !in_array(
                $urgencyLevel,
                $validUrgency,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid urgency level.'
            );
        }

        $this->diseases
            ->updateSeverityAndUrgency(
                $diseaseId,
                $severityLevel,
                $urgencyLevel !== ''
                    ? $urgencyLevel
                    : null,
                $urgencyNoteEn,
                $urgencyNoteHi
            );
    }
}