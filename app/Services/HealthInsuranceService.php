<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HealthInsurancePolicyRepository;
use App\Repositories\HealthProfileRepository;
use RuntimeException;
use App\Repositories\HealthInsuranceMemberRepository;
use App\Repositories\HealthInsuranceContactRepository;
use App\Repositories\HealthDocumentRepository;
use App\Repositories\HealthInsuranceDocumentRepository;

class HealthInsuranceService
{
    public function __construct(
        private HealthInsurancePolicyRepository $policyRepository,
        private HealthInsuranceMemberRepository $memberRepository,
        private HealthInsuranceContactRepository $contactRepository,
        private HealthInsuranceDocumentRepository $insuranceDocumentRepository,
        private HealthDocumentRepository $healthDocumentRepository,
        private HealthProfileRepository $healthProfileRepository,
        private AuthService $authService
    ) {}

    /**
     * Get all insurance policies accessible
     * to the authenticated user.
     */
    public function getPolicies(int $profileId): array
    {
        $this->getAccessibleProfile($profileId);

        return $this->policyRepository
            ->findByProfileId($profileId);
    }

    /**
     * Get a single insurance policy.
     */
    public function getPolicy(
        int $profileId,
        int $policyId
    ): array {
        $this->getAccessibleProfile($profileId);

        $policy = $this->policyRepository
            ->findById($policyId);

        if (
            $policy === null ||
            (int) $policy['health_profile_id'] !== $profileId
        ) {
            throw new RuntimeException(
                'Insurance policy not found.'
            );
        }

        return $policy;
    }

    /**
     * Create an insurance policy.
     */
    public function createPolicy(
        int $profileId,
        array $data
    ): int {
        $this->authorizeWrite($profileId);

        $validated = $this->validatePolicyData($data);

        $validated['health_profile_id'] = $profileId;
        $validated['created_by'] = $this->authenticatedUserId();

        return $this->policyRepository->create($validated);
    }

    /**
     * Update an insurance policy.
     */
    public function updatePolicy(
        int $profileId,
        int $policyId,
        array $data
    ): void {
        $this->authorizeWrite($profileId);

        $this->getPolicy($profileId, $policyId);

        $validated = $this->validatePolicyData($data);

        $updated = $this->policyRepository->update(
            $policyId,
            $validated
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to update the insurance policy.'
            );
        }
    }

    /**
     * Delete an insurance policy.
     */
    public function deletePolicy(
        int $profileId,
        int $policyId
    ): void {
        $this->authorizeWrite($profileId);

        $this->getPolicy($profileId, $policyId);

        $deleted = $this->policyRepository
            ->delete($policyId);

        if (!$deleted) {
            throw new RuntimeException(
                'Unable to delete the insurance policy.'
            );
        }
    }

    /**
     * Get all insured members for a policy.
     */
    public function getMembers(
        int $profileId,
        int $policyId
    ): array {
        $this->getPolicy($profileId, $policyId);

        return $this->memberRepository
            ->findByPolicyId($policyId);
    }

    /**
     * Get one insured member.
     */
    public function getMember(
        int $profileId,
        int $policyId,
        int $memberId
    ): array {
        $this->getPolicy($profileId, $policyId);

        $member = $this->memberRepository
            ->findById($memberId);

        if (
            $member === null ||
            (int) $member['policy_id'] !== $policyId
        ) {
            throw new RuntimeException(
                'Insured member not found.'
            );
        }

        return $member;
    }

    /**
     * Create an insured member.
     */
    public function createMember(
        int $profileId,
        int $policyId,
        array $data
    ): int {
        $this->authorizeWrite($profileId);

        $this->getPolicy($profileId, $policyId);

        $validated = $this->validateMemberData(
            $profileId,
            $data
        );

        $validated['policy_id'] = $policyId;

        return $this->memberRepository
            ->create($validated);
    }

    /**
     * Update an insured member.
     */
    public function updateMember(
        int $profileId,
        int $policyId,
        int $memberId,
        array $data
    ): void {
        $this->authorizeWrite($profileId);

        $this->getMember(
            $profileId,
            $policyId,
            $memberId
        );

        $validated = $this->validateMemberData(
            $profileId,
            $data
        );

        $updated = $this->memberRepository->update(
            $memberId,
            $validated
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to update the insured member.'
            );
        }
    }

    /**
     * Delete an insured member.
     */
    public function deleteMember(
        int $profileId,
        int $policyId,
        int $memberId
    ): void {
        $this->authorizeWrite($profileId);

        $this->getMember(
            $profileId,
            $policyId,
            $memberId
        );

        $deleted = $this->memberRepository
            ->delete($memberId);

        if (!$deleted) {
            throw new RuntimeException(
                'Unable to delete the insured member.'
            );
        }
    }

    /**
     * Validate insured-member data.
     */
    private function validateMemberData(
        int $profileId,
        array $data
    ): array {
        $memberName = trim(
            (string) ($data['member_name'] ?? '')
        );

        if ($memberName === '') {
            throw new RuntimeException(
                'Member name is required.'
            );
        }

        if (mb_strlen($memberName) > 255) {
            throw new RuntimeException(
                'Member name must not exceed 255 characters.'
            );
        }

        $relationship = $data['relationship'] ?? '';

        $allowedRelationships = [
            'self',
            'spouse',
            'child',
            'parent',
            'sibling',
            'other',
        ];

        if (
            !in_array(
                $relationship,
                $allowedRelationships,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid member relationship.'
            );
        }

        $dateOfBirth = $this->normalizeDate(
            $data['date_of_birth'] ?? null,
            false
        );

        $linkedProfileId = !empty(
            $data['health_profile_id']
        )
            ? (int) $data['health_profile_id']
            : null;

        if (
            $linkedProfileId !== null &&
            $linkedProfileId !== $profileId
        ) {
            throw new RuntimeException(
                'The linked health profile is not valid for this insurance record.'
            );
        }

        return [
            'health_profile_id' => (
                !empty($data['health_profile_id'])
                    ? (int) $data['health_profile_id']
                    : null
            ),
            'member_name' => $memberName,
            'relationship' => $relationship,
            'member_id_number' => $this->nullableString(
                $data['member_id_number'] ?? null
            ),
            'health_card_number' => $this->nullableString(
                $data['health_card_number'] ?? null
            ),
            'date_of_birth' => $dateOfBirth,
            'is_primary_insured' => !empty(
                $data['is_primary_insured']
            ) ? 1 : 0,
            'notes' => $this->nullableString(
                $data['notes'] ?? null
            ),
        ];
    }

    /**
     * Get a profile accessible to the current user.
     */
    private function getAccessibleProfile(
        int $profileId
    ): array {
        $userId = $this->authenticatedUserId();

        $profile = $this->healthProfileRepository
            ->findAccessible($profileId, $userId);

        if ($profile === null) {
            throw new RuntimeException(
                'Health profile not found.'
            );
        }

        return $profile;
    }

    /**
     * Verify that the user can modify the profile.
     */
    private function authorizeWrite(
        int $profileId
    ): array {
        $profile = $this->getAccessibleProfile($profileId);

        $role = $profile['role'] ?? null;

        if (
            !in_array(
                $role,
                ['owner', 'editor'],
                true
            )
        ) {
            throw new RuntimeException(
                'You do not have permission to modify insurance records.'
            );
        }

        return $profile;
    }

    /**
     * Ensure the authenticated user is the profile owner.
     *
     * @param int $profileId
     * @return array
     */
    private function authorizeOwner(int $profileId): array
    {
        $userId = $this->authService->id();

        if ($userId === null) {
            throw new \RuntimeException(
                'Authentication required.'
            );
        }

        $profile =
            $this->healthProfileRepository
                ->findAccessible(
                    $profileId,
                    $userId
                );

        if ($profile === null) {
            throw new \RuntimeException(
                'You do not have access to this health profile.'
            );
        }

        if (($profile['role'] ?? null) !== 'owner') {
            throw new \RuntimeException(
                'Only the profile owner can unlink documents.'
            );
        }

        return $profile;
    }

    /**
     * Get the authenticated user's ID.
     */
    private function authenticatedUserId(): int
    {
        $userId = $this->authService->id();

        if (!$userId) {
            throw new RuntimeException(
                'Authentication required.'
            );
        }

        return $userId;
    }

    /**
     * Validate insurance policy data.
     */
    private function validatePolicyData(
        array $data
    ): array {
        $providerName = trim(
            (string) ($data['provider_name'] ?? '')
        );

        if ($providerName === '') {
            throw new RuntimeException(
                'Insurance provider name is required.'
            );
        }

        if (mb_strlen($providerName) > 255) {
            throw new RuntimeException(
                'Insurance provider name must not exceed 255 characters.'
            );
        }

        $policyNumber = trim(
            (string) ($data['policy_number'] ?? '')
        );

        if ($policyNumber === '') {
            throw new RuntimeException(
                'Policy number is required.'
            );
        }

        if (mb_strlen($policyNumber) > 255) {
            throw new RuntimeException(
                'Policy number must not exceed 255 characters.'
            );
        }

        $policyName = $this->nullableString(
            $data['policy_name'] ?? null
        );

        $policyType = $data['policy_type'] ?? 'other';

        $allowedTypes = [
            'individual',
            'family_floater',
            'employer',
            'government',
            'supplementary',
            'other',
        ];

        if (!in_array($policyType, $allowedTypes, true)) {
            throw new RuntimeException(
                'Invalid insurance policy type.'
            );
        }

        $status = $data['status'] ?? 'unknown';

        $allowedStatuses = [
            'active',
            'expired',
            'cancelled',
            'pending',
            'unknown',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new RuntimeException(
                'Invalid insurance policy status.'
            );
        }

        $sumInsured = $data['sum_insured'] ?? null;

        if (
            $sumInsured !== null &&
            $sumInsured !== '' &&
            (
                !is_numeric($sumInsured) ||
                (float) $sumInsured < 0
            )
        ) {
            throw new RuntimeException(
                'Sum insured must be a valid non-negative amount.'
            );
        }

        $startDate = $this->normalizeDate(
            $data['start_date'] ?? null,
            true
        );

        $expiryDate = $this->normalizeDate(
            $data['expiry_date'] ?? null,
            true
        );

        if (
            $startDate !== null &&
            $expiryDate !== null &&
            $expiryDate < $startDate
        ) {
            throw new RuntimeException(
                'Expiry date cannot be earlier than the start date.'
            );
        }

        $currencyCode = strtoupper(
            trim((string) ($data['currency_code'] ?? 'INR'))
        );

        if (
            !preg_match('/^[A-Z]{3}$/', $currencyCode)
        ) {
            throw new RuntimeException(
                'Currency code must contain three letters.'
            );
        }

        return [
            'provider_name' => $providerName,
            'policy_number' => $policyNumber,
            'policy_name' => $policyName,
            'policy_type' => $policyType,
            'status' => $status,
            'sum_insured' => (
                $sumInsured === null || $sumInsured === ''
                    ? null
                    : (string) $sumInsured
            ),
            'currency_code' => $currencyCode,
            'start_date' => $startDate,
            'expiry_date' => $expiryDate,
            'renewal_notes' => $this->nullableString(
                $data['renewal_notes'] ?? null
            ),
            'general_notes' => $this->nullableString(
                $data['general_notes'] ?? null
            ),
        ];
    }

    /**
     * Normalize an optional date.
     */
    private function normalizeDate(
        mixed $value,
        bool $allowFuture = false
    ): ?string {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if (
            !$date ||
            $date->format('Y-m-d') !== $value
        ) {
            throw new RuntimeException(
                'Invalid date format.'
            );
        }

        if (!$allowFuture) {
            $today = new \DateTime('today');

            if ($date > $today) {
                throw new RuntimeException(
                    'Date cannot be in the future.'
                );
            }
        }

        return $value;
    }

    /**
     * Convert an optional value to a trimmed string or null.
     */
    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Get all contacts for a policy.
     */
    public function getContacts(
        int $profileId,
        int $policyId
    ): array {
        $this->getPolicy($profileId, $policyId);

        return $this->contactRepository
            ->findByPolicyId($policyId);
    }

    /**
     * Get one insurance contact.
     */
    public function getContact(
        int $profileId,
        int $policyId,
        int $contactId
    ): array {
        $this->getPolicy($profileId, $policyId);

        $contact = $this->contactRepository
            ->findById($contactId);

        if (
            $contact === null ||
            (int) $contact['policy_id'] !== $policyId
        ) {
            throw new RuntimeException(
                'Insurance contact not found.'
            );
        }

        return $contact;
    }

    /**
     * Create an insurance contact.
     */
    public function createContact(
        int $profileId,
        int $policyId,
        array $data
    ): int {
        $this->authorizeWrite($profileId);

        $this->getPolicy($profileId, $policyId);

        $validatedData = $this->validateContactData($data);

        return $this->contactRepository->create([
            'policy_id' => $policyId,
            ...$validatedData,
        ]);
    }

    /**
     * Update an insurance contact.
     */
    public function updateContact(
        int $profileId,
        int $policyId,
        int $contactId,
        array $data
    ): bool {
        $this->authorizeWrite($profileId);

        $this->getPolicy($profileId, $policyId);

        $contact = $this->contactRepository->findById($contactId);

        if (
            $contact === null ||
            (int) $contact['policy_id'] !== $policyId
        ) {
            throw new RuntimeException(
                'Insurance contact not found.'
            );
        }

        $validatedData = $this->validateContactData($data);

        $updated = $this->contactRepository->update(
            $contactId,
            $validatedData
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to update insurance contact.'
            );
        }

        return true;
    }

    /**
     * Delete an insurance contact.
     */
    public function deleteContact(
        int $profileId,
        int $policyId,
        int $contactId
    ): bool {
        $this->authorizeWrite($profileId);

        $this->getPolicy($profileId, $policyId);

        $contact = $this->contactRepository->findById($contactId);

        if (
            $contact === null ||
            (int) $contact['policy_id'] !== $policyId
        ) {
            throw new RuntimeException(
                'Insurance contact not found.'
            );
        }

        $deleted = $this->contactRepository->delete($contactId);

        if (!$deleted) {
            throw new RuntimeException(
                'Unable to delete insurance contact.'
            );
        }

        return true;
    }

    public function getDocuments(
        int $profileId,
        int $policyId
    ): array {
        $this->getPolicy($profileId, $policyId);

        return $this->insuranceDocumentRepository
            ->findByPolicyId($policyId);
    }

    public function getDocument(
        int $profileId,
        int $policyId,
        int $linkId
    ): array {
        $this->getPolicy($profileId, $policyId);

        $document = $this->insuranceDocumentRepository
            ->findById($linkId);

        if (
            $document === null ||
            (int) $document['policy_id'] !== $policyId
        ) {
            throw new RuntimeException(
                'Insurance document link not found.'
            );
        }

        return $document;
    }

    public function linkDocument(
        int $profileId,
        int $policyId,
        int $documentId,
        ?string $documentLabel = null
    ): int {
        $this->authorizeWrite($profileId);
        $this->getPolicy($profileId, $policyId);

        $document = $this->healthDocumentRepository
            ->findById($documentId);

        if (
            $document === null ||
            (int) $document['health_profile_id'] !== $profileId
        ) {
            throw new RuntimeException(
                'Health document not found.'
            );
        }

        if (
            $this->insuranceDocumentRepository
                ->linkExists($policyId, $documentId)
        ) {
            throw new RuntimeException(
                'This document is already linked to the insurance policy.'
            );
        }

        $validatedLabel = $this->validateDocumentLabel(
            $documentLabel
        );

        return $this->insuranceDocumentRepository->create([
            'policy_id' => $policyId,
            'document_id' => $documentId,
            'document_label' => $validatedLabel,
        ]);
    }

    public function updateDocumentLink(
        int $profileId,
        int $policyId,
        int $linkId,
        ?string $documentLabel = null
    ): bool {
        $this->authorizeWrite($profileId);

        $this->getDocument(
            $profileId,
            $policyId,
            $linkId
        );

        $validatedLabel = $this->validateDocumentLabel(
            $documentLabel
        );

        $updated = $this->insuranceDocumentRepository->update(
            $linkId,
            [
                'document_label' => $validatedLabel,
            ]
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to update insurance document link.'
            );
        }

        return true;
    }

    public function unlinkDocument(
        int $profileId,
        int $policyId,
        int $linkId
    ): bool {
        $this->authorizeOwner($profileId);

        $this->getDocument(
            $profileId,
            $policyId,
            $linkId
        );

        $deleted = $this->insuranceDocumentRepository
            ->delete($linkId);

        if (!$deleted) {
            throw new RuntimeException(
                'Unable to unlink insurance document.'
            );
        }

        return true;
    }

    private function validateDocumentLabel(
        ?string $documentLabel
    ): ?string {
        if ($documentLabel === null) {
            return null;
        }

        $documentLabel = trim($documentLabel);

        if ($documentLabel === '') {
            return null;
        }

        if (mb_strlen($documentLabel) > 255) {
            throw new RuntimeException(
                'Document label must not exceed 255 characters.'
            );
        }

        return $documentLabel;
    }

    private function validateContactData(array $data): array
    {
        $allowedTypes = [
            'insurer',
            'tpa',
            'claims',
            'other',
        ];

        $contactType = trim(
            (string) ($data['contact_type'] ?? '')
        );

        if (!in_array($contactType, $allowedTypes, true)) {
            throw new RuntimeException(
                'Invalid insurance contact type.'
            );
        }

        $organizationName = trim(
            (string) ($data['organization_name'] ?? '')
        );

        if ($organizationName === '') {
            throw new RuntimeException(
                'Organization name is required.'
            );
        }

        if (mb_strlen($organizationName) > 255) {
            throw new RuntimeException(
                'Organization name must not exceed 255 characters.'
            );
        }

        $contactPerson = trim(
            (string) ($data['contact_person'] ?? '')
        );

        $phone = trim(
            (string) ($data['phone'] ?? '')
        );

        $alternatePhone = trim(
            (string) ($data['alternate_phone'] ?? '')
        );

        $email = trim(
            (string) ($data['email'] ?? '')
        );

        $website = trim(
            (string) ($data['website'] ?? '')
        );

        $address = trim(
            (string) ($data['address'] ?? '')
        );

        $notes = trim(
            (string) ($data['notes'] ?? '')
        );

        if ($contactPerson !== '' && mb_strlen($contactPerson) > 255) {
            throw new RuntimeException(
                'Contact person must not exceed 255 characters.'
            );
        }

        if ($phone !== '' && mb_strlen($phone) > 50) {
            throw new RuntimeException(
                'Phone number must not exceed 50 characters.'
            );
        }

        if ($alternatePhone !== '' && mb_strlen($alternatePhone) > 50) {
            throw new RuntimeException(
                'Alternate phone number must not exceed 50 characters.'
            );
        }

        if ($email !== '') {
            if (mb_strlen($email) > 255) {
                throw new RuntimeException(
                    'Email must not exceed 255 characters.'
                );
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException(
                    'Please enter a valid email address.'
                );
            }
        }

        if ($website !== '') {
            if (mb_strlen($website) > 500) {
                throw new RuntimeException(
                    'Website URL must not exceed 500 characters.'
                );
            }

            if (!filter_var($website, FILTER_VALIDATE_URL)) {
                throw new RuntimeException(
                    'Please enter a valid website URL.'
                );
            }
        }

        if (mb_strlen($address) > 1000) {
            throw new RuntimeException(
                'Address must not exceed 1000 characters.'
            );
        }

        if (mb_strlen($notes) > 2000) {
            throw new RuntimeException(
                'Notes must not exceed 2000 characters.'
            );
        }

        return [
            'contact_type' => $contactType,
            'organization_name' => $organizationName,
            'contact_person' => $contactPerson !== ''
                ? $contactPerson
                : null,
            'phone' => $phone !== ''
                ? $phone
                : null,
            'alternate_phone' => $alternatePhone !== ''
                ? $alternatePhone
                : null,
            'email' => $email !== ''
                ? $email
                : null,
            'website' => $website !== ''
                ? $website
                : null,
            'address' => $address !== ''
                ? $address
                : null,
            'notes' => $notes !== ''
                ? $notes
                : null,
        ];
    }

    /**
     * Get health documents that are available to link
     * with the selected insurance policy.
     *
     * @param int $profileId
     * @param int $policyId
     * @return array
     */
    public function getAvailableDocuments(
        int $profileId,
        int $policyId
    ): array {
        // Validate policy access.
        $this->getPolicy($profileId, $policyId);

        // Get all documents belonging to the profile.
        $documents =
            $this->healthDocumentRepository
                ->findByProfileId($profileId);

        // Get documents already linked to this policy.
        $linkedDocuments =
            $this->insuranceDocumentRepository
                ->findByPolicyId($policyId);

        // Build a lookup of linked document IDs.
        $linkedDocumentIds = [];

        foreach ($linkedDocuments as $linkedDocument) {
            $linkedDocumentIds[
                (int) $linkedDocument['document_id']
            ] = true;
        }

        // Exclude documents already linked to the policy.
        return array_values(
            array_filter(
                $documents,
                static function (array $document) use (
                    $linkedDocumentIds
                ): bool {
                    return !isset(
                        $linkedDocumentIds[(int) $document['id']]
                    );
                }
            )
        );
    }

}