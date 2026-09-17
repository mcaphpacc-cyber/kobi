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
use App\Services\HealthDocumentService;
use App\Services\HealthProfileInvitationService;

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
        private MedicalSummaryShareService $medicalSummaryShareService,
        private HealthDocumentService $healthDocumentService,
        private HealthProfileInvitationService $healthProfileInvitationService
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
            $this->getAccessibleHealthProfile($profileId);

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/conditions'
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
            if (
                $exception->getMessage()
                === 'You do not have permission to modify this health profile.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/conditions'
                );
            }

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/conditions'
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
            if (
                $exception->getMessage()
                === 'You do not have permission to modify this health profile.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/conditions'
                );
            }

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
            if (
                $exception->getMessage()
                === 'You do not have permission to modify this health profile.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/conditions'
                );
            }

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
            $this->getAccessibleHealthProfile($profileId);

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/medicines'
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
            if (
                $exception->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/medicines'
                );
            }

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
                        $exception->getMessage(),

                    'old' =>
                        $_POST
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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/medicines'
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
            if (
                $exception->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/medicines'
                );
            }

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
            if (
                $exception->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/medicines'
                );
            }

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

        $profile =
            $this->getAccessibleHealthProfile($profileId);

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


    public function createHealthAllergy(
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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/allergies'
            );
        }

        $this->view(
            'account/health-allergy-create',
            [
                'profile' => $profile
            ]
        );
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
            if (
                $e->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/allergies'
                );
            }

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/allergies'
            );
        }

        $allergy =
            $this->healthAllergyService
                ->getAllergy(
                    $profileId,
                    $allergyId
                );

        if (!$allergy)
        {
            throw new RuntimeException(
                'Allergy not found.'
            );
        }

        $this->view(
            'account/health-allergy-edit',
            [
                'profile' => $profile,
                'allergy' => $allergy
            ]
        );
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
            if (
                $e->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/allergies'
                );
            }

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

        $profile =
            $this->getAccessibleHealthProfile($profileId);

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


    public function createHealthProcedure(
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

        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            redirect(
                '/account/health-records/' .
                $profileId .
                '/procedures'
            );
        }

        $this->view(
            'account/health-procedure-create',
            [
                'profile' => $profile
            ]
        );
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

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            if (
                !in_array(
                    $profile['role'] ?? null,
                    ['owner', 'editor'],
                    true
                )
            ){
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/procedures'
                );
            }

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
            if (
                $e->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/procedures'
                );
            }

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

            if (
                !in_array(
                    $profile['role'] ?? null,
                    ['owner', 'editor'],
                    true
                )
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/procedures'
                );
            }

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/procedures'
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
            if (
                $e->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/procedures'
                );
            }

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

    public function healthDocuments(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->getAccessibleHealthProfile($profileId);

        $documents =
            $this->healthDocumentService
                ->getDocuments($profileId);

        $this->view(
            'account/health-documents',
            [
                'profile' => $profile,
                'documents' => $documents
            ]
        );
    }


    public function createHealthDocument(
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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/documents'
            );
        }

        $this->view(
            'account/health-document-create',
            [
                'profile' => $profile
            ]
        );
    }

    public function storeHealthDocument(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthDocumentService
                ->createDocument(
                    $profileId,
                    $_FILES['document'] ?? [],
                    [
                        'title' =>
                            $_POST['title'] ?? '',

                        'document_type' =>
                            $_POST['document_type'] ?? 'other',

                        'document_date' =>
                            $_POST['document_date'] ?? '',

                        'hospital' =>
                            $_POST['hospital'] ?? '',

                        'doctor' =>
                            $_POST['doctor'] ?? '',

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/documents'
            );
        }
        catch (RuntimeException $e)
        {
            if (
                $e->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/documents'
                );
            }

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-document-create',
                [
                    'profile' => $profile,
                    'error' => $e->getMessage()
                ]
            );
        }
    }


    public function editHealthDocument(
        int $profileId,
        int $documentId
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
                !in_array(
                    $profile['role'] ?? null,
                    ['owner', 'editor'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'You do not have permission to edit medical documents.'
                );
            }

            $document =
                $this->healthDocumentService
                    ->getDocument($documentId);

            if (
                (int) $document['health_profile_id']
                !== $profileId
            ) {
                throw new RuntimeException(
                    'Medical document not found.'
                );
            }

            $this->view(
                'account/health-document-edit',
                [
                    'profile' => $profile,
                    'document' => $document
                ]
            );
        }
        catch (RuntimeException $e)
        {
            redirect(
                '/account/health-records/' .
                $profileId .
                '/documents'
            );
        }
    }

    public function updateHealthDocument(
        int $profileId,
        int $documentId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $document =
                $this->healthDocumentService
                    ->getDocument($documentId);

            if (
                (int) $document['health_profile_id']
                !== $profileId
            ) {
                throw new RuntimeException(
                    'Medical document not found.'
                );
            }

            $this->healthDocumentService
                ->updateDocument(
                    $documentId,
                    [
                        'title' =>
                            $_POST['title'] ?? '',

                        'document_type' =>
                            $_POST['document_type'] ?? 'other',

                        'document_date' =>
                            $_POST['document_date'] ?? '',

                        'hospital' =>
                            $_POST['hospital'] ?? '',

                        'doctor' =>
                            $_POST['doctor'] ?? '',

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/documents'
            );
        }
        catch (RuntimeException $e)
        {
            if (
                $e->getMessage()
                === 'You do not have permission to modify this health record.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/documents'
                );
            }

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $document =
                $this->healthDocumentService
                    ->getDocument($documentId);

            $this->view(
                'account/health-document-edit',
                [
                    'profile' => $profile,
                    'document' => $document,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    public function healthDocumentFile(
        int $profileId,
        int $documentId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $document =
                $this->healthDocumentService
                    ->getDocument($documentId);

            if (
                (int) $document['health_profile_id']
                !== $profileId
            ) {
                throw new RuntimeException(
                    'Medical document not found.'
                );
            }

            $filePath =
                $this->healthDocumentService
                    ->getFilePath($documentId);

            $mimeType =
                trim(
                    (string) (
                        $document['mime_type'] ?? ''
                    )
                );

            if ($mimeType === '')
            {
                throw new RuntimeException(
                    'Medical document MIME type is unavailable.'
                );
            }

            $originalFilename =
                trim(
                    (string) (
                        $document['original_filename']
                        ?? 'medical-document'
                    )
                );

            $safeFilename =
                str_replace(
                    [
                        "\r",
                        "\n",
                        '"'
                    ],
                    '',
                    $originalFilename
                );

            if ($safeFilename === '')
            {
                $safeFilename = 'medical-document';
            }

            header(
                'Content-Type: ' .
                $mimeType
            );

            header(
                'Content-Length: ' .
                (string) filesize($filePath)
            );

            header(
                'Content-Disposition: inline; filename="' .
                $safeFilename .
                '"'
            );

            header(
                'X-Content-Type-Options: nosniff'
            );

            header(
                'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0'
            );

            header(
                'Pragma: no-cache'
            );

            readfile($filePath);
        }
        catch (RuntimeException $e)
        {
            http_response_code(404);

            $this->view(
                'account/health-document-error',
                [
                    'error' =>
                        'Medical document could not be opened.'
                ]
            );
        }
    }

    public function deleteHealthDocument(
        int $profileId,
        int $documentId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $document =
                $this->healthDocumentService
                    ->getDocument($documentId);

            if (
                (int) $document['health_profile_id']
                !== $profileId
            ) {
                throw new RuntimeException(
                    'Medical document not found.'
                );
            }

            $this->healthDocumentService
                ->deleteDocument($documentId);

            redirect(
                '/account/health-records/' .
                $profileId .
                '/documents'
            );
        }
        catch (RuntimeException $e)
        {
            redirect(
                '/account/health-records/' .
                $profileId .
                '/documents'
            );
        }
    }

    public function healthTimeline(
        int $profileId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        $profile =
            $this->getAccessibleHealthProfile($profileId);

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

    public function medicalSummary(
        int $profileId
    ): void
    {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

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

    private function getAccessibleHealthProfile(
        int $profileId
    ): array {
        try
        {
            return $this->healthProfileService
                ->getProfile($profileId);
        }
        catch (RuntimeException $exception)
        {
            if (
                $exception->getMessage()
                === 'Health profile not found.'
            ) {
                redirect('/account/health-records');
            }

            throw $exception;
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
            $this->getAccessibleHealthProfile($profileId);

        $documentCount =
            $this->healthDocumentService
                ->getDocumentCount($profileId);

        $this->view(
            'account/health-record',
            [
                'title' => $profile['full_name']
                    . ' - Health Record',

                'profile' => $profile,

                'documentCount' =>
                    $documentCount
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
            redirect(
                '/account/health-records/' .
                $profileId
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
            if (
                $exception->getMessage()
                === 'You do not have permission to edit this health profile.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId
                );
            }

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/medical-summary'
            );
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
            if (
                $exception->getMessage()
                === 'Only the health profile owner can share a medical summary.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/medical-summary'
                );
            }

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
            redirect(
                '/account/health-records/' .
                $profileId .
                '/medical-summary'
            );
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
            if (
                $exception->getMessage()
                === 'Only the health profile owner can revoke a medical summary share.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/medical-summary'
                );
            }

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

    /**
     * Manage access to a health profile.
     */
    public function healthRecordAccess(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $access =
                $this->healthProfileInvitationService
                    ->getAccessManagement($profileId);

            $this->view(
                'account/health-record-access',
                [
                    'title' =>
                        $access['profile']['full_name']
                        . ' - Manage Access',

                    'profile' =>
                        $access['profile'],

                    'activeMembers' =>
                        $access['activeMembers'],

                    'revokedMembers' =>
                        $access['revokedMembers'],

                    'pendingInvitations' =>
                        $access['pendingInvitations']
                ]
            );
        }
        catch (\RuntimeException $e)
        {
            redirect(
                '/account/health-records/' .
                $profileId
            );
        }
    }

    /**
     * Create a health profile access invitation.
     */
    public function storeHealthRecordInvitation(
        int $profileId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $email =
                $_POST['email'] ?? '';

            $role =
                $_POST['role'] ?? 'viewer';

            $expiresInDays =
                (int) (
                    $_POST['expires_in_days']
                    ?? 7
                );

            $token =
                $this->healthProfileInvitationService
                    ->createInvitation(
                        $profileId,
                        $email,
                        $role,
                        $expiresInDays
                    );

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-record-invitation-created',
                [
                    'title' =>
                        'Invitation Created',

                    'profile' =>
                        $profile,

                    'token' =>
                        $token
                ]
            );
        }
        catch (\Throwable $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            if (
                ($profile['role'] ?? null) !== 'owner'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId
                );
            }

            $access =
                $this->healthProfileInvitationService
                    ->getAccessManagement($profileId);

            $this->view(
                'account/health-record-access',
                [
                    'title' =>
                        'Manage Access - ' .
                        $profile['full_name'],

                    'profile' =>
                        $profile,

                    'activeMembers' =>
                        $access['activeMembers'],

                    'revokedMembers' =>
                        $access['revokedMembers'],

                    'pendingInvitations' =>
                        $access['pendingInvitations'],

                    'error' =>
                        $exception->getMessage(),

                    'formEmail' =>
                        $_POST['email'] ?? '',

                    'formRole' =>
                        $_POST['role'] ?? 'viewer',

                    'formExpiry' =>
                        $_POST['expires_in_days'] ?? '7'
                ]
            );
        }
    }

    /**
     * Display a health profile invitation.
     */
    public function healthProfileInvitation(
        string $token
    ): void {
        $token = trim($token);

        if ($token === '')
        {
            abort(
                404,
                'Invitation not found.'
            );
        }

        if (!$this->authService->check())
        {
            $this->session->put(
                'pending_health_profile_invitation',
                $token
            );

            redirect('/login');
        }

        try
        {
            /*
            * Validate the invitation without accepting it.
            */
            $invitation =
                $this->healthProfileInvitationService
                    ->getInvitationForAcceptance($token);

            $this->view(
                'account/health-profile-invitation',
                [
                    'title' => 'Health Profile Invitation',
                    'invitation' => $invitation,
                    'token' => $token
                ]
            );
        }
        catch (\RuntimeException $exception)
        {
            $this->view(
                'account/health-profile-invitation-error',
                [
                    'title' => 'Health Profile Invitation',
                    'message' => $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Accept a health profile invitation.
     */
    public function acceptHealthProfileInvitation(
        string $token
    ): void {
        if (!$this->authService->check())
        {
            $this->session->put(
                'pending_health_profile_invitation',
                trim($token)
            );

            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $profile =
                $this->healthProfileInvitationService
                    ->acceptInvitation($token);

            redirect(
                '/account/health-records/' .
                (int) $profile['id']
            );
        }
        catch (\RuntimeException $exception)
        {
            $this->view(
                'account/health-profile-invitation-error',
                [
                    'title' =>
                        'Health Profile Invitation',

                    'message' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Update a health profile member's role.
     */
    public function updateHealthRecordMemberRole(
        int $profileId,
        int $memberUserId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $role =
                $_POST['role'] ?? '';

            $this->healthProfileInvitationService
                ->updateMemberRole(
                    $profileId,
                    $memberUserId,
                    (string) $role
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/access'
            );
        }
        catch (\RuntimeException $exception)
        {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            if (
                ($profile['role'] ?? null) !== 'owner'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId
                );
            }

            $access =
                $this->healthProfileInvitationService
                    ->getAccessManagement($profileId);

            $this->view(
                'account/health-record-access',
                [
                    'title' =>
                        'Manage Access - ' .
                        $access['profile']['full_name'],

                    'profile' =>
                        $access['profile'],

                    'activeMembers' =>
                        $access['activeMembers'],

                    'revokedMembers' =>
                        $access['revokedMembers'],

                    'pendingInvitations' =>
                        $access['pendingInvitations'],

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Revoke a health profile member's access.
     */
    public function revokeHealthRecordMember(
        int $profileId,
        int $memberUserId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $this->healthProfileInvitationService
                ->revokeMember(
                    $profileId,
                    $memberUserId
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/access'
            );
        }
        catch (\RuntimeException $exception)
        {
            if (
                $exception->getMessage()
                === 'Only the health profile owner can revoke member access.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId
                );
            }

            $access =
                $this->healthProfileInvitationService
                    ->getAccessManagement($profileId);

            $this->view(
                'account/health-record-access',
                [
                    'title' =>
                        'Manage Access - ' .
                        $access['profile']['full_name'],

                    'profile' =>
                        $access['profile'],

                    'activeMembers' =>
                        $access['activeMembers'],

                    'revokedMembers' =>
                        $access['revokedMembers'],

                    'pendingInvitations' =>
                        $access['pendingInvitations'],

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Restore a revoked health profile member.
     */
    public function reactivateHealthRecordMember(
        int $profileId,
        int $memberUserId
    ): void {
        if (!$this->authService->check())
        {
            redirect('/login');
        }

        try
        {
            $this->verifyCsrf();

            $role =
                $_POST['role'] ?? 'viewer';

            $this->healthProfileInvitationService
                ->reactivateMember(
                    $profileId,
                    $memberUserId,
                    (string) $role
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/access'
            );
        }
        catch (\RuntimeException $exception)
        {
            if (
                $exception->getMessage()
                === 'Only the health profile owner can restore member access.'
            ) {
                redirect(
                    '/account/health-records/' .
                    $profileId
                );
            }

            $access =
                $this->healthProfileInvitationService
                    ->getAccessManagement($profileId);

            $this->view(
                'account/health-record-access',
                [
                    'title' =>
                        'Manage Access - ' .
                        $access['profile']['full_name'],

                    'profile' =>
                        $access['profile'],

                    'activeMembers' =>
                        $access['activeMembers'],

                    'revokedMembers' =>
                        $access['revokedMembers'],

                    'pendingInvitations' =>
                        $access['pendingInvitations'],

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

}