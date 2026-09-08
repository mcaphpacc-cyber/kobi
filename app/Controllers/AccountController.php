<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;
use RuntimeException;
use App\Services\SavedDiseaseService;
use App\Services\TreatmentPreferenceService;
use App\Services\HealthProfileService;
use App\Services\HealthConditionService;
use App\Services\HealthMedicineService;
use App\Services\HealthAllergyService;
use App\Services\HealthProcedureService;
use App\Services\HealthTimelineService;
use App\Services\MedicalSummaryService;
use App\Services\MedicalSummaryShareService;

class AccountController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private Session $session,
        private SavedDiseaseService $savedDiseaseService,
        private TreatmentPreferenceService $treatmentPreferenceService,
        private HealthProfileService $healthProfileService,
        private HealthConditionService $healthConditionService,
        private HealthMedicineService $healthMedicineService,
        private HealthAllergyService $healthAllergyService,
        private HealthProcedureService $healthProcedureService,
        private HealthTimelineService $healthTimelineService,
        private MedicalSummaryService $medicalSummaryService,
        private MedicalSummaryShareService $medicalSummaryShareService
    ) {
    }

    public function index(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $user = $this->authService->user();

        $this->view(
            'account/index',
            [
                'title' => 'My Account',
                'user'  => $user
            ]
        );
    }

    public function savedDiseases(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $savedDiseases =
            $this->savedDiseaseService
                ->getSavedDiseases();

        $this->view(
            'account/saved-diseases',
            [
                'title' => 'Saved Diseases',
                'savedDiseases' => $savedDiseases
            ]
        );
    }

    public function healthRecords(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profiles =
            $this->healthProfileService
                ->getAccessibleProfiles();

        $this->view(
            'account/health-records',
            [
                'title' => 'My Health Records',
                'profiles' => $profiles
            ]
        );
    }

    public function healthConditions(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $conditions =
            $this->healthConditionService
                ->getConditions($profileId);

        $this->view(
            'account/health-conditions',
            [
                'title' =>
                    $profile['full_name'] .
                    ' - Medical Conditions',

                'profile' => $profile,
                'conditions' => $conditions
            ]
        );
    }

    public function createHealthCondition(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to add a medical condition.'
            );
        }

        $this->view(
            'account/health-condition-create',
            [
                'title' =>
                    'Add Medical Condition - ' .
                    $profile['full_name'],

                'profile' => $profile
            ]
        );
    }

    public function storeHealthCondition(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthConditionService
                ->createCondition(
                    $profileId,
                    [
                        'condition_name' =>
                            $_POST['condition_name'] ?? '',

                        'status' =>
                            $_POST['status'] ?? 'active',

                        'diagnosed_on' =>
                            $_POST['diagnosed_on'] ?? '',

                        'resolved_on' =>
                            $_POST['resolved_on'] ?? '',

                        'doctor_hospital' =>
                            $_POST['doctor_hospital'] ?? '',

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/conditions'
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-condition-create',
                [
                    'title' =>
                        'Add Medical Condition - ' .
                        $profile['full_name'],

                    'profile' => $profile,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function editHealthCondition(
        int $profileId,
        int $conditionId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to edit medical conditions.'
            );
        }

        $condition =
            $this->healthConditionService
                ->getCondition(
                    $profileId,
                    $conditionId
                );

        if (!$condition)
        {
            throw new RuntimeException(
                'Medical condition not found.'
            );
        }

        $this->view(
            'account/health-condition-edit',
            [
                'title' =>
                    'Edit Medical Condition - ' .
                    $profile['full_name'],

                'profile' => $profile,
                'condition' => $condition
            ]
        );
    }

    public function updateHealthCondition(
        int $profileId,
        int $conditionId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthConditionService
                ->updateCondition(
                    $profileId,
                    $conditionId,
                    [
                        'condition_name' =>
                            $_POST['condition_name'] ?? '',

                        'status' =>
                            $_POST['status'] ?? 'active',

                        'diagnosed_on' =>
                            $_POST['diagnosed_on'] ?? '',

                        'resolved_on' =>
                            $_POST['resolved_on'] ?? '',

                        'doctor_hospital' =>
                            $_POST['doctor_hospital'] ?? '',

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/conditions'
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $condition =
                $this->healthConditionService
                    ->getCondition(
                        $profileId,
                        $conditionId
                    );

            $this->view(
                'account/health-condition-edit',
                [
                    'title' =>
                        'Edit Medical Condition - ' .
                        $profile['full_name'],

                    'profile' => $profile,
                    'condition' => $condition,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function deleteHealthCondition(
        int $profileId,
        int $conditionId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthConditionService
                ->deleteCondition(
                    $profileId,
                    $conditionId
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/conditions'
            );
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }
    }

    public function healthMedicines(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $medicines =
            $this->healthMedicineService
                ->getMedicines($profileId);

        $this->view(
            'account/health-medicines',
            [
                'title' =>
                    $profile['full_name'] .
                    ' - Current Medicines',

                'profile' => $profile,
                'medicines' => $medicines
            ]
        );
    }

    public function createHealthMedicine(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to add a medicine.'
            );
        }

        $this->view(
            'account/health-medicine-create',
            [
                'title' =>
                    'Add Medicine - ' .
                    $profile['full_name'],

                'profile' => $profile
            ]
        );
    }

    public function storeHealthMedicine(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthMedicineService
                ->createMedicine(
                    $profileId,
                    [
                        'brand_name' =>
                            $_POST['brand_name'] ?? '',

                        'generic_name' =>
                            $_POST['generic_name'] ?? '',

                        'strength' =>
                            $_POST['strength'] ?? '',

                        'dose' =>
                            $_POST['dose'] ?? '',

                        'frequency' =>
                            $_POST['frequency'] ?? '',

                        'route' =>
                            $_POST['route'] ?? '',

                        'status' =>
                            $_POST['status'] ?? 'current',

                        'started_on' =>
                            $_POST['started_on'] ?? '',

                        'stopped_on' =>
                            $_POST['stopped_on'] ?? '',

                        'prescribed_by' =>
                            $_POST['prescribed_by'] ?? '',

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/medicines'
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-medicine-create',
                [
                    'title' =>
                        'Add Medicine - ' .
                        $profile['full_name'],

                    'profile' => $profile,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function editHealthMedicine(
        int $profileId,
        int $medicineId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to edit medicines.'
            );
        }

        $medicine =
            $this->healthMedicineService
                ->getMedicine(
                    $profileId,
                    $medicineId
                );

        if (!$medicine)
        {
            throw new RuntimeException(
                'Medicine not found.'
            );
        }

        $this->view(
            'account/health-medicine-edit',
            [
                'title' =>
                    'Edit Medicine - ' .
                    $profile['full_name'],

                'profile' => $profile,
                'medicine' => $medicine
            ]
        );
    }

    public function updateHealthMedicine(
        int $profileId,
        int $medicineId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthMedicineService
                ->updateMedicine(
                    $profileId,
                    $medicineId,
                    [
                        'brand_name' =>
                            $_POST['brand_name'] ?? '',

                        'generic_name' =>
                            $_POST['generic_name'] ?? '',

                        'strength' =>
                            $_POST['strength'] ?? '',

                        'dose' =>
                            $_POST['dose'] ?? '',

                        'frequency' =>
                            $_POST['frequency'] ?? '',

                        'route' =>
                            $_POST['route'] ?? '',

                        'status' =>
                            $_POST['status'] ?? 'current',

                        'started_on' =>
                            $_POST['started_on'] ?? '',

                        'stopped_on' =>
                            $_POST['stopped_on'] ?? '',

                        'prescribed_by' =>
                            $_POST['prescribed_by'] ?? '',

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/medicines'
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $medicine =
                $this->healthMedicineService
                    ->getMedicine(
                        $profileId,
                        $medicineId
                    );

            $this->view(
                'account/health-medicine-edit',
                [
                    'title' =>
                        'Edit Medicine - ' .
                        $profile['full_name'],

                    'profile' => $profile,
                    'medicine' => $medicine,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function deleteHealthMedicine(
        int $profileId,
        int $medicineId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthMedicineService
                ->deleteMedicine(
                    $profileId,
                    $medicineId
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/medicines'
            );
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }
    }

    public function healthAllergies(
        int $profileId
    ) {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $allergies =
                $this->healthAllergyService
                    ->getAllergies($profileId);

            return $this->view(
                'account/health-allergies',
                [
                    'profile' => $profile,
                    'allergies' => $allergies
                ]
            );
        }
        catch (RuntimeException $e)
        {
            return $this->view(
                'account/health-record',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function createHealthAllergy(
        int $profileId
    ) {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            return $this->view(
                'account/health-allergy-create',
                [
                    'profile' => $profile
                ]
            );
        }
        catch (RuntimeException $e)
        {
            return $this->view(
                'account/health-record',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function storeHealthAllergy(
        int $profileId
    ) {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthAllergyService
                ->createAllergy(
                    $profileId,
                    $_POST
                );

            return redirect(
                '/account/health-records/' .
                $profileId .
                '/allergies'
            );
        }
        catch (RuntimeException $e)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            return $this->view(
                'account/health-allergy-create',
                [
                    'profile' => $profile,
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function editHealthAllergy(
        int $profileId,
        int $allergyId
    ) {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $allergy =
                $this->healthAllergyService
                    ->getAllergy(
                        $profileId,
                        $allergyId
                    );

            return $this->view(
                'account/health-allergy-edit',
                [
                    'profile' => $profile,
                    'allergy' => $allergy
                ]
            );
        }
        catch (RuntimeException $e)
        {
            return $this->view(
                'account/health-record',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function updateHealthAllergy(
        int $profileId,
        int $allergyId
    ) {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthAllergyService
                ->updateAllergy(
                    $profileId,
                    $allergyId,
                    $_POST
                );

            return redirect(
                '/account/health-records/' .
                $profileId .
                '/allergies'
            );
        }
        catch (RuntimeException $e)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $allergy =
                $this->healthAllergyService
                    ->getAllergy(
                        $profileId,
                        $allergyId
                    );

            return $this->view(
                'account/health-allergy-edit',
                [
                    'profile' => $profile,
                    'allergy' => $allergy,
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function deleteHealthAllergy(
        int $profileId,
        int $allergyId
    ) {
        if (!$this->authService->check())
        {
            redirect('/login');
        }
        try
        {
            $this->verifyCsrf();

            $this->healthAllergyService
                ->deleteAllergy(
                    $profileId,
                    $allergyId
                );

            return redirect(
                '/account/health-records/' .
                $profileId .
                '/allergies'
            );
        }
        catch (RuntimeException $e)
        {
            return redirect(
                '/account/health-records/' .
                $profileId .
                '/allergies'
            );
        }
    }

    public function healthProcedures(
        int $profileId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $procedures =
                $this->healthProcedureService
                    ->getProcedures($profileId);

            $this->view(
                'account/health-procedures',
                [
                    'profile' => $profile,
                    'procedures' => $procedures
                ]
            );
        }
        catch (RuntimeException $e)
        {
            $this->view(
                'account/health-record',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function createHealthProcedure(
        int $profileId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-procedure-create',
                [
                    'profile' => $profile
                ]
            );
        }
        catch (RuntimeException $e)
        {
            $this->view(
                'account/health-record',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function storeHealthProcedure(
        int $profileId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthProcedureService
                ->createProcedure(
                    $profileId,
                    $_POST
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/procedures'
            );
        }
        catch (RuntimeException $e)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-procedure-create',
                [
                    'profile' => $profile,
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function editHealthProcedure(
        int $profileId,
        int $procedureId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $procedure =
                $this->healthProcedureService
                    ->getProcedure(
                        $profileId,
                        $procedureId
                    );

            $this->view(
                'account/health-procedure-edit',
                [
                    'profile' => $profile,
                    'procedure' => $procedure
                ]
            );
        }
        catch (RuntimeException $e)
        {
            $this->view(
                'account/health-record',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function updateHealthProcedure(
        int $profileId,
        int $procedureId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthProcedureService
                ->updateProcedure(
                    $profileId,
                    $procedureId,
                    $_POST
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/procedures'
            );
        }
        catch (RuntimeException $e)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $procedure =
                $this->healthProcedureService
                    ->getProcedure(
                        $profileId,
                        $procedureId
                    );

            $this->view(
                'account/health-procedure-edit',
                [
                    'profile' => $profile,
                    'procedure' => $procedure,
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function deleteHealthProcedure(
        int $profileId,
        int $procedureId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthProcedureService
                ->deleteProcedure(
                    $profileId,
                    $procedureId
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/procedures'
            );
        }
        catch (RuntimeException $e)
        {
            redirect(
                '/account/health-records/' .
                $profileId .
                '/procedures'
            );
        }
    }

    public function healthTimeline(int $profileId): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $timeline =
                $this->healthTimelineService
                    ->getTimeline($profileId);

            $this->view(
                'account/health-timeline',
                [
                    'profile' => $profile,
                    'timeline' => $timeline
                ]
            );
        }
        catch (\Throwable $e)
        {
            $this->view(
                'account/health-record',
                [
                    'profile' => null,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    public function medicalSummary(int $profileId): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $summary =
                $this->medicalSummaryService
                    ->getSummary($profileId);

            $this->view(
                'account/medical-summary',
                [
                    'summary' => $summary
                ]
            );
        }
        catch (\Throwable $e)
        {
            $this->view(
                'account/health-record',
                [
                    'profile' => null,
                    'error' => $e->getMessage()
                ]
            );
        }
    }



    public function updateProfile(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->authService->updateProfile(
                $_POST['name'] ?? '',
                $_POST['email'] ?? ''
            );

            redirect('/account?profile_updated=1');

        }
        catch (RuntimeException $exception)
        {
            $user = $this->authService->user();

            $this->view(
                'account/index',
                [
                    'title' => 'My Account',
                    'user'  => $user,
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    private function verifyCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? '';

        if (
            !$this->session->verifyCsrfToken(
                $token
            )
        ) {
            throw new RuntimeException(
                'Invalid security token. Please try again.'
            );
        }
    }

    public function changePassword(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->authService->changePassword(
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? '',
                $_POST['new_password_confirmation'] ?? ''
            );

            redirect('/account?password_updated=1');

        }
        catch (RuntimeException $exception)
        {
            $user = $this->authService->user();

            $this->view(
                'account/index',
                [
                    'title' => 'My Account',
                    'user'  => $user,
                    'passwordError' => $exception->getMessage()
                ]
            );
        }
    }

    public function treatmentPreferences(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $treatmentSystems =
            $this->treatmentPreferenceService
                ->getTreatmentSystems();

        $preferences =
            $this->treatmentPreferenceService
                ->getPreferences();

        $this->view(
            'account/treatment-preferences',
            [
                'title' => 'Treatment Preferences',
                'treatmentSystems' => $treatmentSystems,
                'preferences' => $preferences
            ]
        );
    }

    public function saveTreatmentPreferences(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $this->verifyCsrf();

        $order =
            $_POST['treatment_system_ids'] ?? [];

        if (!is_array($order))
        {
            $order = [];
        }

        $this->treatmentPreferenceService
            ->savePreferences($order);

        redirect('/account/treatment-preferences');
    }

    public function resetTreatmentPreferences(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $this->verifyCsrf();

        $this->treatmentPreferenceService
            ->resetPreferences();

        redirect('/account/treatment-preferences');
    }

    public function healthRecord(
        int $profileId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $this->view(
            'account/health-record',
            [
                'title' => $profile['full_name']
                    . ' - Health Record',

                'profile' => $profile
            ]
        );
    }

    public function createHealthRecord(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $this->view(
            'account/health-record-create',
            [
                'title' => 'Add Family Health Profile'
            ]
        );
    }

    public function storeHealthRecord(): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $fullName =
                trim($_POST['full_name'] ?? '');

            $dateOfBirth =
                trim($_POST['date_of_birth'] ?? '');

            $gender =
                $_POST['gender'] ?? 'unspecified';

            $this->healthProfileService
                ->createFamilyProfile(
                    $fullName,
                    $dateOfBirth !== ''
                        ? $dateOfBirth
                        : null,
                    $gender
                );

            redirect('/account/health-records');
        }
        catch (\Throwable $exception)
        {
            $this->view(
                'account/health-record-create',
                [
                    'title' => 'Add Family Health Profile',
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    public function editHealthRecord(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to edit this health profile.'
            );
        }

        $this->view(
            'account/health-record-edit',
            [
                'title' =>
                    'Edit ' .
                    $profile['full_name'] .
                    ' Health Profile',

                'profile' => $profile
            ]
        );
    }

    public function updateHealthRecord(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $fullName =
                trim(
                    $_POST['full_name'] ?? ''
                );

            $dateOfBirth =
                trim(
                    $_POST['date_of_birth'] ?? ''
                );

            $gender =
                $_POST['gender'] ?? 'unspecified';

            $this->healthProfileService
                ->updateProfile(
                    $profileId,
                    $fullName,
                    $dateOfBirth !== ''
                        ? $dateOfBirth
                        : null,
                    $gender
                );

            redirect(
                '/account/health-records/' .
                $profileId
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-record-edit',
                [
                    'title' =>
                        'Edit ' .
                        $profile['full_name'] .
                        ' Health Profile',

                    'profile' => $profile,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function createMedicalSummaryShare(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            if (
                ($profile['role'] ?? null)
                !== 'owner'
            ) {
                throw new RuntimeException(
                    'Only the health profile owner can share a medical summary.'
                );
            }

            $this->view(
                'account/medical-summary-share-create',
                [
                    'title' =>
                        'Share Medical Summary - ' .
                        $profile['full_name'],

                    'profile' => $profile
                ]
            );
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }
    }

    public function storeMedicalSummaryShare(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $expiresInDays =
                (int) (
                    $_POST['expires_in_days']
                    ?? 7
                );

            $share =
                $this->medicalSummaryShareService
                    ->createShare(
                        $profileId,
                        $expiresInDays
                    );

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            /*
            * The raw token is returned only at creation time.
            * It is not retrieved from the database later.
            */
            $shareUrl =
                url(
                    '/shared/medical-summary/' .
                    $share['token']
                );

            $this->view(
                'account/medical-summary-share-created',
                [
                    'title' =>
                        'Medical Summary Share Created',

                    'profile' =>
                        $profile,

                    'share' =>
                        $share,

                    'shareUrl' =>
                        $shareUrl
                ]
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/medical-summary-share-create',
                [
                    'title' =>
                        'Share Medical Summary - ' .
                        $profile['full_name'],

                    'profile' =>
                        $profile,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function medicalSummaryShares(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $shares =
                $this->medicalSummaryShareService
                    ->getShares($profileId);

            $this->view(
                'account/medical-summary-shares',
                [
                    'title' =>
                        'Medical Summary Shares - ' .
                        $profile['full_name'],

                    'profile' =>
                        $profile,

                    'shares' =>
                        $shares
                ]
            );
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }
    }

    public function revokeMedicalSummaryShare(
        int $profileId,
        int $shareId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->medicalSummaryShareService
                ->revokeShare(
                    $shareId
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/medical-summary/shares'
            );
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }
    }

    public function sharedMedicalSummary(
        string $token
    ): void {
        header(
            'X-Robots-Tag: noindex, nofollow, noarchive'
        );

        header(
            'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
        );

        header(
            'Pragma: no-cache'
        );

        header(
            'X-Frame-Options: DENY'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        header(
            'Referrer-Policy: no-referrer'
        );
        try
        {
            $result =
                $this->medicalSummaryShareService
                    ->getPublicShare($token);

            $this->view(
                'shared/medical-summary',
                [
                    'summary' =>
                        $result['snapshot'],

                    'share' =>
                        $result['share']
                ]
            );
        }
        catch (\Throwable $exception)
        {
            $this->view(
                'shared/medical-summary-error',
                [
                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

}