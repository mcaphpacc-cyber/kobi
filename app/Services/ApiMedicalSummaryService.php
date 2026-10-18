<?php
declare(strict_types=1);
namespace App\Services;

final class ApiMedicalSummaryService
{
    public function __construct(private MedicalSummaryService $summary) {}

    public function detail(ApiAuthPrincipal $principal, string $profileId): array
    {
        $summary = $this->summary->getSummaryForPrincipal($principal, $profileId);
        // Never serialize the assembled clinical records or a raw profile row.
        return [
            'profile' => [
                'id' => $summary['profile']['id'],
                'full_name' => $summary['profile']['full_name'],
                'profile_type' => $summary['profile']['profile_type'],
            ],
            'counts' => [
                'conditions' => count($summary['conditions']['active']),
                'historical_conditions' => count($summary['conditions']['historical']),
                'current_medications' => count($summary['medicines']['current']),
                'allergies' => count($summary['allergies']),
                'procedures' => count($summary['procedures']),
            ],
        ];
    }
}
