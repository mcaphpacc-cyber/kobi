<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Services\AuthService;
use App\Services\HealthInsuranceService;
use App\Services\HealthProfileService;
use RuntimeException;

class HealthInsuranceController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private Session $session,
        private HealthProfileService $healthProfileService,
        private HealthInsuranceService $healthInsuranceService
    ) {
    }

    /**
     * Display insurance policies for a health profile.
     */
    public function index(int $profileId): void
    {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $policies =
            $this->healthInsuranceService
                ->getPolicies($profileId);

        $this->view(
            'account/health-insurance',
            [
                'title' =>
                    $profile['full_name'] .
                    ' - Health Insurance',

                'profile' => $profile,
                'policies' => $policies
            ]
        );
    }

    /**
     * Display create policy form.
     */
    public function create(int $profileId): void
    {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $this->authorizeWrite($profile);

        $this->view(
            'account/health-insurance-create',
            [
                'title' =>
                    'Add Health Insurance - ' .
                    $profile['full_name'],

                'profile' => $profile
            ]
        );
    }

    /**
     * Store a new insurance policy.
     */
    public function store(int $profileId): void
    {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $this->healthInsuranceService
                ->createPolicy(
                    $profileId,
                    [
                        'provider_name' =>
                            $_POST['provider_name'] ?? '',

                        'policy_number' =>
                            $_POST['policy_number'] ?? '',

                        'policy_name' =>
                            $_POST['policy_name'] ?? '',

                        'policy_type' =>
                            $_POST['policy_type'] ?? 'other',

                        'status' =>
                            $_POST['status'] ?? 'unknown',

                        'sum_insured' =>
                            $_POST['sum_insured'] ?? '',

                        'currency_code' =>
                            $_POST['currency_code'] ?? 'INR',

                        'start_date' =>
                            $_POST['start_date'] ?? '',

                        'expiry_date' =>
                            $_POST['expiry_date'] ?? '',

                        'renewal_notes' =>
                            $_POST['renewal_notes'] ?? '',

                        'general_notes' =>
                            $_POST['general_notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/insurance'
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->view(
                'account/health-insurance-create',
                [
                    'title' =>
                        'Add Health Insurance - ' .
                        $profile['full_name'],

                    'profile' => $profile,
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Display edit policy form.
     */
    public function edit(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $this->authorizeWrite($profile);

        $policy =
            $this->healthInsuranceService
                ->getPolicy(
                    $profileId,
                    $policyId
                );

        $this->view(
            'account/health-insurance-edit',
            [
                'title' =>
                    'Edit Health Insurance - ' .
                    $profile['full_name'],

                'profile' => $profile,
                'policy' => $policy
            ]
        );
    }

    /**
     * Update an existing insurance policy.
     */
    public function update(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $this->healthInsuranceService
                ->updatePolicy(
                    $profileId,
                    $policyId,
                    [
                        'provider_name' =>
                            $_POST['provider_name'] ?? '',

                        'policy_number' =>
                            $_POST['policy_number'] ?? '',

                        'policy_name' =>
                            $_POST['policy_name'] ?? '',

                        'policy_type' =>
                            $_POST['policy_type'] ?? 'other',

                        'status' =>
                            $_POST['status'] ?? 'unknown',

                        'sum_insured' =>
                            $_POST['sum_insured'] ?? '',

                        'currency_code' =>
                            $_POST['currency_code'] ?? 'INR',

                        'start_date' =>
                            $_POST['start_date'] ?? '',

                        'expiry_date' =>
                            $_POST['expiry_date'] ?? '',

                        'renewal_notes' =>
                            $_POST['renewal_notes'] ?? '',

                        'general_notes' =>
                            $_POST['general_notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/insurance'
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy(
                        $profileId,
                        $policyId
                    );

            $this->view(
                'account/health-insurance-edit',
                [
                    'title' =>
                        'Edit Health Insurance - ' .
                        $profile['full_name'],

                    'profile' => $profile,
                    'policy' => $policy,
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Delete an insurance policy.
     */
    public function delete(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $this->verifyCsrf();

        $this->healthInsuranceService
            ->deletePolicy(
                $profileId,
                $policyId
            );

        redirect(
            '/account/health-records/' .
            $profileId .
            '/insurance'
        );
    }

    /**
     * Require an authenticated user.
     */
    private function requireAuthentication(): void
    {
        if (!$this->authService->check()) {
            redirect('/login');
        }
    }

    /**
     * Authorize policy creation/editing.
     */
    private function authorizeWrite(array $profile): void
    {
        if (
            !in_array(
                $profile['role'] ?? null,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to manage health insurance.'
            );
        }
    }

    /**
     * Verify CSRF token.
     */
    private function verifyCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? '';

        if (
            !$this->session->verifyCsrfToken($token)
        ) {
            throw new RuntimeException(
                'Invalid security token. Please try again.'
            );
        }
    }

    public function show(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $profile = $this->healthProfileService
            ->getProfile($profileId);

        $policy = $this->healthInsuranceService
            ->getPolicy($profileId, $policyId);

        $members = $this->healthInsuranceService
            ->getMembers($profileId, $policyId);

        $contacts = $this->healthInsuranceService
            ->getContacts($profileId, $policyId);
        
        $documents =
            $this->healthInsuranceService->getDocuments(
                $profileId,
                $policyId
            );

        $this->view(
            'account/health-insurance-details',
            [
                'title' => 'Insurance Policy Details',
                'profile' => $profile,
                'policy' => $policy,
                'members' => $members,
                'contacts' => $contacts,
                'documents' => $documents,
            ]
        );
    }

    /**
     * Show the form to link a health document
     * with an insurance policy.
     */
    public function createDocument(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $this->authorizeWrite($profile);

        $policy =
            $this->healthInsuranceService
                ->getPolicy($profileId, $policyId);

        $documents =
            $this->healthInsuranceService
                ->getAvailableDocuments(
                    $profileId,
                    $policyId
                );

        $this->view(
            'account/health-insurance-document-create',
            [
                'title' => 'Link Health Document',
                'profile' => $profile,
                'policy' => $policy,
                'documents' => $documents,
            ]
        );
    }

    /**
     * Store a document link against an insurance policy.
     */
    public function storeDocument(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->authorizeWrite($profile);

            $documentId =
                (int) ($_POST['document_id'] ?? 0);

            $documentLabel =
                trim(
                    (string) (
                        $_POST['document_label'] ?? ''
                    )
                );

            $this->healthInsuranceService
                ->linkDocument(
                    $profileId,
                    $policyId,
                    $documentId,
                    $documentLabel !== ''
                        ? $documentLabel
                        : null
                );

            redirect(
                '/account/health-records/'
                . $profileId
                . '/insurance/'
                . $policyId
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy($profileId, $policyId);

            $documents =
                $this->healthInsuranceService
                    ->getAvailableDocuments(
                        $profileId,
                        $policyId
                    );

            $this->view(
                'account/health-insurance-document-create',
                [
                    'title' => 'Link Health Document',
                    'profile' => $profile,
                    'policy' => $policy,
                    'documents' => $documents,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Show the form to edit a linked document.
     */
    public function editDocument(
        int $profileId,
        int $policyId,
        int $linkId
    ): void {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $this->authorizeWrite($profile);

        $policy =
            $this->healthInsuranceService
                ->getPolicy($profileId, $policyId);

        $document =
            $this->healthInsuranceService
                ->getDocument(
                    $profileId,
                    $policyId,
                    $linkId
                );

        $this->view(
            'account/health-insurance-document-edit',
            [
                'title' => 'Edit Linked Document',
                'profile' => $profile,
                'policy' => $policy,
                'document' => $document,
            ]
        );
    }

    /**
     * Update a linked document's label.
     */
    public function updateDocument(
        int $profileId,
        int $policyId,
        int $linkId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->authorizeWrite($profile);

            $documentLabel =
                trim(
                    (string) (
                        $_POST['document_label'] ?? ''
                    )
                );

            $this->healthInsuranceService
                ->updateDocumentLink(
                    $profileId,
                    $policyId,
                    $linkId,
                    $documentLabel !== ''
                        ? $documentLabel
                        : null
                );

            redirect(
                '/account/health-records/'
                . $profileId
                . '/insurance/'
                . $policyId
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy($profileId, $policyId);

            $document =
                $this->healthInsuranceService
                    ->getDocument(
                        $profileId,
                        $policyId,
                        $linkId
                    );

            $this->view(
                'account/health-insurance-document-edit',
                [
                    'title' => 'Edit Linked Document',
                    'profile' => $profile,
                    'policy' => $policy,
                    'document' => $document,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Unlink a document from an insurance policy.
     *
     * The original health document is not deleted.
     */
    public function unlinkDocument(
        int $profileId,
        int $policyId,
        int $linkId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $this->authorizeWrite($profile);

            $this->healthInsuranceService
                ->unlinkDocument(
                    $profileId,
                    $policyId,
                    $linkId
                );

            redirect(
                '/account/health-records/'
                . $profileId
                . '/insurance/'
                . $policyId
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy($profileId, $policyId);

            $members =
                $this->healthInsuranceService
                    ->getMembers($profileId, $policyId);

            $contacts =
                $this->healthInsuranceService
                    ->getContacts($profileId, $policyId);

            $documents =
                $this->healthInsuranceService
                    ->getDocuments($profileId, $policyId);

            $this->view(
                'account/health-insurance-details',
                [
                    'title' => 'Insurance Policy Details',
                    'profile' => $profile,
                    'policy' => $policy,
                    'members' => $members,
                    'contacts' => $contacts,
                    'documents' => $documents,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    public function createContact(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $profile = $this->healthProfileService
            ->getProfile($profileId);

        $this->authorizeWrite($profile);

        $policy = $this->healthInsuranceService
            ->getPolicy($profileId, $policyId);

        $this->view(
            'account/health-insurance-contact-create',
            [
                'title' => 'Add Insurance Contact',
                'profile' => $profile,
                'policy' => $policy,
            ]
        );
    }

    public function editContact(
        int $profileId,
        int $policyId,
        int $contactId
    ): void {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService->getProfile($profileId);

        $this->authorizeWrite($profile);

        $policy =
            $this->healthInsuranceService->getPolicy(
                $profileId,
                $policyId
            );

        $contact =
            $this->healthInsuranceService->getContact(
                $profileId,
                $policyId,
                $contactId
            );

        $this->view(
            'account/health-insurance-contact-edit',
            [
                'profile' => $profile,
                'policy' => $policy,
                'contact' => $contact,
            ]
        );
    }

    public function updateContact(
        int $profileId,
        int $policyId,
        int $contactId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $profile =
                $this->healthProfileService->getProfile($profileId);

            $this->authorizeWrite($profile);

            $this->healthInsuranceService->updateContact(
                $profileId,
                $policyId,
                $contactId,
                [
                    'contact_type' =>
                        $_POST['contact_type'] ?? '',

                    'organization_name' =>
                        $_POST['organization_name'] ?? '',

                    'contact_person' =>
                        $_POST['contact_person'] ?? '',

                    'phone' =>
                        $_POST['phone'] ?? '',

                    'alternate_phone' =>
                        $_POST['alternate_phone'] ?? '',

                    'email' =>
                        $_POST['email'] ?? '',

                    'website' =>
                        $_POST['website'] ?? '',

                    'address' =>
                        $_POST['address'] ?? '',

                    'notes' =>
                        $_POST['notes'] ?? '',
                ]
            );

            redirect(
                "/account/health-records/{$profileId}/insurance/{$policyId}"
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService->getProfile($profileId);

            $policy =
                $this->healthInsuranceService->getPolicy(
                    $profileId,
                    $policyId
                );

            $contact =
                $this->healthInsuranceService->getContact(
                    $profileId,
                    $policyId,
                    $contactId
                );

            $this->view(
                'account/health-insurance-contact-edit',
                [
                    'profile' => $profile,
                    'policy' => $policy,
                    'contact' => $contact,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    public function deleteContact(
        int $profileId,
        int $policyId,
        int $contactId
    ): void {
        $this->requireAuthentication();

        $this->verifyCsrf();

        $profile =
            $this->healthProfileService->getProfile($profileId);

        $this->authorizeWrite($profile);

        $this->healthInsuranceService->deleteContact(
            $profileId,
            $policyId,
            $contactId
        );

        redirect(
            "/account/health-records/{$profileId}/insurance/{$policyId}"
        );
    }

    public function storeContact(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $this->verifyCsrf();

        $profile = $this->healthProfileService
            ->getProfile($profileId);

        $this->authorizeWrite($profile);

        $policy = $this->healthInsuranceService
            ->getPolicy($profileId, $policyId);

        try {
            $this->healthInsuranceService->createContact(
                $profileId,
                $policyId,
                [
                    'contact_type' => $_POST['contact_type'] ?? null,
                    'organization_name' => $_POST['organization_name'] ?? null,
                    'contact_person' => $_POST['contact_person'] ?? null,
                    'phone' => $_POST['phone'] ?? null,
                    'alternate_phone' => $_POST['alternate_phone'] ?? null,
                    'email' => $_POST['email'] ?? null,
                    'website' => $_POST['website'] ?? null,
                    'address' => $_POST['address'] ?? null,
                    'notes' => $_POST['notes'] ?? null,
                ]
            );

            redirect(
                    '/account/health-records/' .
                    $profileId .
                    '/insurance/' .
                    $policyId
            );
        } catch (\Throwable $exception) {
            $this->view(
                'account/health-insurance-contact-create',
                [
                    'title' => 'Add Insurance Contact',
                    'profile' => $profile,
                    'policy' => $policy,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    public function createMember(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $policy =
            $this->healthInsuranceService
                ->getPolicy(
                    $profileId,
                    $policyId
                );

        $this->authorizeWrite($profile);

        $this->view(
            'account/health-insurance-member-create',
            [
                'title' => 'Add Insured Member',

                'profile' => $profile,

                'policy' => $policy
            ]
        );
    }

    public function storeMember(
        int $profileId,
        int $policyId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $this->healthInsuranceService
                ->createMember(
                    $profileId,
                    $policyId,
                    [
                        'health_profile_id' =>
                            $_POST['health_profile_id'] ?? null,

                        'member_name' =>
                            $_POST['member_name'] ?? '',

                        'relationship' =>
                            $_POST['relationship'] ?? 'other',

                        'member_id_number' =>
                            $_POST['member_id_number'] ?? '',

                        'health_card_number' =>
                            $_POST['health_card_number'] ?? '',

                        'date_of_birth' =>
                            $_POST['date_of_birth'] ?? '',

                        'is_primary_insured' =>
                            isset(
                                $_POST['is_primary_insured']
                            ),

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/insurance/' .
                $policyId
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy(
                        $profileId,
                        $policyId
                    );

            $this->view(
                'account/health-insurance-member-create',
                [
                    'title' => 'Add Insured Member',

                    'profile' => $profile,

                    'policy' => $policy,

                    'error' =>
                        $exception->getMessage()
                ]
            );
        }
    }

    public function editMember(
        int $profileId,
        int $policyId,
        int $memberId
    ): void {
        $this->requireAuthentication();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        $policy =
            $this->healthInsuranceService
                ->getPolicy(
                    $profileId,
                    $policyId
                );

        $member =
            $this->healthInsuranceService
                ->getMember(
                    $profileId,
                    $policyId,
                    $memberId
                );

        $this->authorizeWrite($profile);

        $this->view(
            'account/health-insurance-member-edit',
            [
                'title' => 'Edit Insured Member',
                'profile' => $profile,
                'policy' => $policy,
                'member' => $member
            ]
        );
    }

    public function updateMember(
        int $profileId,
        int $policyId,
        int $memberId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $this->healthInsuranceService
                ->updateMember(
                    $profileId,
                    $policyId,
                    $memberId,
                    [
                        'health_profile_id' =>
                            $_POST['health_profile_id'] ?? null,

                        'member_name' =>
                            $_POST['member_name'] ?? '',

                        'relationship' =>
                            $_POST['relationship'] ?? 'other',

                        'member_id_number' =>
                            $_POST['member_id_number'] ?? '',

                        'health_card_number' =>
                            $_POST['health_card_number'] ?? '',

                        'date_of_birth' =>
                            $_POST['date_of_birth'] ?? '',

                        'is_primary_insured' =>
                            isset(
                                $_POST['is_primary_insured']
                            ),

                        'notes' =>
                            $_POST['notes'] ?? ''
                    ]
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/insurance/' .
                $policyId
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy(
                        $profileId,
                        $policyId
                    );

            $member =
                $this->healthInsuranceService
                    ->getMember(
                        $profileId,
                        $policyId,
                        $memberId
                    );

            $this->view(
                'account/health-insurance-member-edit',
                [
                    'title' => 'Edit Insured Member',
                    'profile' => $profile,
                    'policy' => $policy,
                    'member' => $member,
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    public function deleteMember(
        int $profileId,
        int $policyId,
        int $memberId
    ): void {
        $this->requireAuthentication();

        try {
            $this->verifyCsrf();

            $this->healthInsuranceService
                ->deleteMember(
                    $profileId,
                    $policyId,
                    $memberId
                );

            redirect(
                '/account/health-records/' .
                $profileId .
                '/insurance/' .
                $policyId
            );
        } catch (\Throwable $exception) {
            $profile =
                $this->healthProfileService
                    ->getProfile($profileId);

            $policy =
                $this->healthInsuranceService
                    ->getPolicy(
                        $profileId,
                        $policyId
                    );

            $members =
                $this->healthInsuranceService
                    ->getMembers(
                        $profileId,
                        $policyId
                    );

            $this->view(
                'account/health-insurance-details',
                [
                    'title' => 'Insurance Policy',
                    'profile' => $profile,
                    'policy' => $policy,
                    'members' => $members,
                    'error' => $exception->getMessage()
                ]
            );
        }
    }
}