<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class HealthDashboardService
{
    public function __construct(
        private AuthService $authService,
        private HealthProfileService $healthProfileService,
        private MedicalSummaryService $medicalSummaryService,
        private HealthDocumentService $healthDocumentService,
        private SavedDiseaseService $savedDiseaseService
    ) {
    }

    public function getDashboard(mixed $requestedProfileId = null): array
    {
        if ($this->authService->id() === null) {
            throw new RuntimeException('Authentication required.');
        }

        $profiles = array_map($this->profileProjection(...), $this->healthProfileService->getAccessibleProfiles());
        $selected = null;
        $selectionUnavailable = false;

        if ($requestedProfileId !== null) {
            $id = is_string($requestedProfileId) || is_int($requestedProfileId)
                ? filter_var($requestedProfileId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;
            foreach ($profiles as $profile) {
                if ($id !== false && $profile['id'] === $id) {
                    $selected = $profile;
                    break;
                }
            }
            $selectionUnavailable = $selected === null;
        } else {
            $self = array_values(array_filter($profiles, static fn (array $profile): bool =>
                $profile['profile_type'] === 'self' && $profile['role'] === 'owner'
            ));
            if (count($self) === 1) {
                $selected = $self[0];
            } elseif (count($profiles) === 1) {
                $selected = $profiles[0];
            }
        }

        $counts = null;
        if ($selected !== null) {
            try {
                // The summary and document services each recheck current profile access.
                $summary = $this->medicalSummaryService->getSummary($selected['id']);
                $documents = $this->healthDocumentService->getDocumentCount($selected['id']);
                $selected = $this->profileProjection($summary['profile']);
                $counts = [
                    'conditions' => count($summary['conditions']['active']),
                    'historical_conditions' => count($summary['conditions']['historical']),
                    'allergies' => count($summary['allergies']),
                    'procedures' => count($summary['procedures']),
                    'current_medications' => count($summary['medicines']['current']),
                    'documents' => $documents,
                ];
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() !== 'Health profile not found.') {
                    throw $exception;
                }
                // Access may have been revoked after the initial profile list read.
                $profiles = array_map($this->profileProjection(...), $this->healthProfileService->getAccessibleProfiles());
                $selected = null;
                $selectionUnavailable = true;
            }
        }

        $savedDiseaseCount = count($this->savedDiseaseService->getSavedDiseases());

        return [
            'profiles' => $profiles,
            'selected_profile' => $selected,
            'selection_unavailable' => $selectionUnavailable,
            'counts' => $counts,
            'saved_disease_count' => $savedDiseaseCount,
            'guidance' => $this->guidance($profiles, $selected, $counts, $savedDiseaseCount),
        ];
    }

    private function guidance(array $profiles, ?array $selected, ?array $counts, int $savedDiseaseCount): array
    {
        $actions = [];
        if ($selected === null) {
            $actions[] = $profiles === []
                ? $this->action('profile', 'Set up a health profile', 'Choose the existing profile workflow to organize information you want to keep in KOBI.', 'Open Health Records', '/account/health-records')
                : $this->action('profile', 'Choose a health profile', 'Select an accessible profile to view its recorded information and available next steps.', 'View health profiles', '/account/health-records');
        } elseif (in_array($selected['role'], ['owner', 'editor'], true)) {
            $path = '/account/health-records/' . $selected['id'];
            if ($counts['conditions'] + $counts['historical_conditions'] === 0) {
                $actions[] = $this->action('conditions', 'Record a condition if relevant', 'You can add a condition if you want to keep it in your KOBI health record.', 'Open Conditions', $path . '/conditions');
            }
            if ($counts['allergies'] === 0) {
                $actions[] = $this->action('allergies', 'Record allergies if relevant', 'You can add allergies or sensitivities if you want them included in your health record.', 'Open Allergies', $path . '/allergies');
            }
            if ($counts['procedures'] === 0) {
                $actions[] = $this->action('procedures', 'Organize procedure records', 'You can add relevant procedures if you want to keep that history together.', 'Open Procedures', $path . '/procedures');
            }
            if ($counts['current_medications'] === 0) {
                $actions[] = $this->action('medications', 'Record current medications if relevant', 'If you take medications, you can record them in your current medication list.', 'Open Personal Medications', $path . '/medicines');
            }
            if ($counts['documents'] === 0) {
                $actions[] = $this->action('documents', 'Organize your health documents', 'You can add health documents you want to keep organized in KOBI.', 'Open Documents', $path . '/documents');
            }
        }

        if ($savedDiseaseCount === 0) {
            $actions[] = $this->action('saved_diseases', 'Save reading for later', 'You can save diseases you want to revisit while exploring KOBI.', 'Open Saved Diseases', '/account/saved-diseases');
        }

        // A summary is always derived for an accessible profile, including an empty one.
        // When no optional setup prompt applies, offer its existing read-only destination.
        if ($actions === [] && $selected !== null) {
            $actions[] = $this->action('summary', 'Your Medical Summary is available', 'View a summary of the information recorded in this profile whenever useful.', 'View Medical Summary', '/account/health-records/' . $selected['id'] . '/medical-summary');
        }

        return array_slice($actions, 0, 3);
    }

    private function action(string $key, string $title, string $description, string $label, string $path): array
    {
        return compact('key', 'title', 'description', 'label', 'path');
    }

    private function profileProjection(array $profile): array
    {
        return [
            'id' => (int) $profile['id'],
            'full_name' => $profile['full_name'],
            'profile_type' => $profile['profile_type'],
            'role' => $profile['role'],
        ];
    }
}
