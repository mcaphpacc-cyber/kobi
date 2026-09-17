<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HealthProfileInvitationRepository;
use App\Repositories\HealthProfileRepository;
use App\Repositories\UserRepository;
use RuntimeException;

class HealthProfileInvitationService
{
    private const MIN_EXPIRY_DAYS = 1;

    private const MAX_EXPIRY_DAYS = 30;

    public function __construct(
        private HealthProfileInvitationRepository $invitationRepository,
        private HealthProfileRepository $healthProfileRepository,
        private UserRepository $userRepository,
        private AuthService $authService,
        private HealthProfileService $healthProfileService
    ) {}

    /**
     * Create an invitation for a health profile.
     *
     * Returns the raw invitation token.
     *
     * The raw token must never be stored in the database.
     */
    public function createInvitation(
        int $profileId,
        string $email,
        string $role,
        int $expiresInDays = 7
    ): string {
        $userId = $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can invite members.'
            );
        }

        $email = strtolower(trim($email));

        if ($email === '')
        {
            throw new RuntimeException(
                'Email address is required.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new RuntimeException(
                'Please enter a valid email address.'
            );
        }

        $role = strtolower(trim($role));

        if (!in_array(
            $role,
            ['editor', 'viewer'],
            true
        ))
        {
            throw new RuntimeException(
                'Invalid member role.'
            );
        }

        if (
            $expiresInDays < self::MIN_EXPIRY_DAYS ||
            $expiresInDays > self::MAX_EXPIRY_DAYS
        )
        {
            throw new RuntimeException(
                'Invitation expiry must be between 1 and 30 days.'
            );
        }

        $invitedUser =
            $this->userRepository
                ->findByEmail($email);

        if ($invitedUser !== null)
        {
            if (
                (int) $invitedUser['id'] === $userId
            )
            {
                throw new RuntimeException(
                    'You cannot invite yourself.'
                );
            }

            $existingMember =
                $this->healthProfileRepository
                    ->findMember(
                        $profileId,
                        (int) $invitedUser['id']
                    );

            if (
                $existingMember !== null &&
                $existingMember['status'] === 'active'
            )
            {
                throw new RuntimeException(
                    'This user already has access to the health profile.'
                );
            }
        }

        $existingInvitation =
            $this->invitationRepository
                ->findPendingByProfileAndEmail(
                    $profileId,
                    $email
                );

        if ($existingInvitation !== null)
        {
            $expiresAt =
                new \DateTime(
                    $existingInvitation['expires_at']
                );

            $now = new \DateTime();

            if ($expiresAt > $now)
            {
                throw new RuntimeException(
                    'A pending invitation already exists for this email address.'
                );
            }
        }

        $token =
            bin2hex(random_bytes(32));

        $tokenHash =
            hash('sha256', $token);

        $expiresAt =
            new \DateTime();

        $expiresAt->modify(
            '+' . $expiresInDays . ' days'
        );

        $this->invitationRepository->create(
            [
                'health_profile_id' => $profileId,
                'invited_by' => $userId,
                'email' => $email,
                'role' => $role,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt->format(
                    'Y-m-d H:i:s'
                )
            ]
        );

        return $token;
    }

    /**
     * Get pending invitations for a profile.
     */
    public function getPendingInvitations(
        int $profileId
    ): array {
        $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can manage invitations.'
            );
        }

        $invitations =
            $this->invitationRepository
                ->findPendingByProfileId($profileId);

        $now = new \DateTime();

        foreach ($invitations as &$invitation)
        {
            $expiresAt =
                new \DateTime(
                    $invitation['expires_at']
                );

            $invitation['is_expired'] =
                $expiresAt <= $now;

            $invitation['display_status'] =
                $invitation['is_expired']
                    ? 'expired'
                    : 'pending';
        }

        unset($invitation);

        return $invitations;
    }

    /**
     * Revoke a pending invitation.
     */
    public function revokeInvitation(
        int $profileId,
        int $invitationId
    ): void {
        $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can revoke invitations.'
            );
        }

        $invitation =
            $this->invitationRepository
                ->findById($invitationId);

        if ($invitation === null)
        {
            throw new RuntimeException(
                'Invitation not found.'
            );
        }

        if (
            (int) $invitation['health_profile_id']
            !== $profileId
        )
        {
            throw new RuntimeException(
                'Invitation does not belong to this health profile.'
            );
        }

        if ($invitation['status'] !== 'pending')
        {
            throw new RuntimeException(
                'Only pending invitations can be revoked.'
            );
        }

        $revoked =
            $this->invitationRepository
                ->revoke($invitationId);

        if (!$revoked)
        {
            throw new RuntimeException(
                'Unable to revoke the invitation.'
            );
        }
    }

    /**
     * Accept an invitation using its raw token.
     */
    public function acceptInvitation(
        string $token
    ): array {
        $userId = $this->authenticatedUserId();

        $token = trim($token);

        if ($token === '')
        {
            throw new RuntimeException(
                'Invalid invitation.'
            );
        }

        $tokenHash =
            hash('sha256', $token);

        $invitation =
            $this->invitationRepository
                ->findByTokenHash($tokenHash);

        if ($invitation === null)
        {
            throw new RuntimeException(
                'Invalid or expired invitation.'
            );
        }

        if ($invitation['status'] !== 'pending')
        {
            throw new RuntimeException(
                'This invitation is no longer available.'
            );
        }

        $expiresAt =
            new \DateTime(
                $invitation['expires_at']
            );

        $now = new \DateTime();

        if ($expiresAt <= $now)
        {
            throw new RuntimeException(
                'This invitation has expired.'
            );
        }

        $user =
            $this->userRepository
                ->findById($userId);

        if ($user === null)
        {
            throw new RuntimeException(
                'Authenticated user could not be determined.'
            );
        }

        $userEmail =
            strtolower(trim($user['email']));

        $invitationEmail =
            strtolower(trim($invitation['email']));

        if ($userEmail !== $invitationEmail)
        {
            throw new RuntimeException(
                'This invitation was issued for a different email address.'
            );
        }

        $profile =
            $this->healthProfileRepository
                ->findAccessible(
                    (int) $invitation['health_profile_id'],
                    $userId
                );

        if ($profile !== null)
        {
            throw new RuntimeException(
                'You already have access to this health profile.'
            );
        }

        $this->invitationRepository
            ->acceptWithMembership(
                (int) $invitation['id'],
                (int) $invitation['health_profile_id'],
                $userId,
                $invitation['role']
            );

        return $this->healthProfileService
            ->getProfile(
                (int) $invitation['health_profile_id']
            );
    }

    /**
     * Get the currently authenticated user ID.
     */
    private function authenticatedUserId(): int
    {
        if (!$this->authService->check())
        {
            throw new RuntimeException(
                'Authentication required.'
            );
        }

        $userId =
            $this->authService->id();

        if ($userId === null)
        {
            throw new RuntimeException(
                'Authenticated user could not be determined.'
            );
        }

        return $userId;
    }

    /**
     * Get the complete access-management data
     * for a health profile.
     */
    public function getAccessManagement(
        int $profileId
    ): array {
        $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can manage access.'
            );
        }

        $members =
            $this->healthProfileRepository
                ->findMembersByProfileId($profileId);

        $invitations =
            $this->getPendingInvitations($profileId);

        $activeMembers = [];
        $revokedMembers = [];

        foreach ($members as $member)
        {
            if ($member['status'] === 'active')
            {
                $activeMembers[] = $member;
            }
            else
            {
                $revokedMembers[] = $member;
            }
        }

        return [
            'profile' => $profile,
            'activeMembers' => $activeMembers,
            'revokedMembers' => $revokedMembers,
            'pendingInvitations' => $invitations
        ];
    }

    /**
     * Get invitation details for the acceptance page.
     *
     * This validates the invitation but does not accept it.
     */
    public function getInvitationForAcceptance(
        string $token
    ): array {
        $userId = $this->authenticatedUserId();

        $token = trim($token);

        if ($token === '')
        {
            throw new RuntimeException(
                'Invalid invitation.'
            );
        }

        $tokenHash =
            hash('sha256', $token);

        $invitation =
            $this->invitationRepository
                ->findByTokenHash($tokenHash);

        if ($invitation === null)
        {
            throw new RuntimeException(
                'Invalid or expired invitation.'
            );
        }

        if ($invitation['status'] !== 'pending')
        {
            throw new RuntimeException(
                'This invitation is no longer available.'
            );
        }

        $expiresAt =
            new \DateTime(
                $invitation['expires_at']
            );

        $now = new \DateTime();

        if ($expiresAt <= $now)
        {
            throw new RuntimeException(
                'This invitation has expired.'
            );
        }

        $user =
            $this->userRepository
                ->findById($userId);

        if ($user === null)
        {
            throw new RuntimeException(
                'Authenticated user could not be determined.'
            );
        }

        $userEmail =
            strtolower(trim($user['email']));

        $invitationEmail =
            strtolower(trim($invitation['email']));

        if ($userEmail !== $invitationEmail)
        {
            throw new RuntimeException(
                'This invitation was issued for a different email address.'
            );
        }

        $profile =
            $this->healthProfileRepository
                ->findAccessible(
                    (int) $invitation['health_profile_id'],
                    $userId
                );

        if ($profile !== null)
        {
            throw new RuntimeException(
                'You already have access to this health profile.'
            );
        }

        return [
            'email' =>
                $invitation['email'],

            'role' =>
                $invitation['role'],

            'expires_at' =>
                $invitation['expires_at'],

            'profile_name' =>
                $this->getProfileName(
                    (int) $invitation['health_profile_id']
                )
        ];
    }

    /**
     * Get the name of an invited health profile.
     */
    private function getProfileName(
        int $profileId
    ): string {
        $profile =
            $this->healthProfileRepository
                ->findActiveById($profileId);

        if ($profile === null)
        {
            throw new RuntimeException(
                'Health profile not found.'
            );
        }

        return (string) $profile['full_name'];
    }

    /**
     * Change the role of an existing health profile member.
     */
    public function updateMemberRole(
        int $profileId,
        int $memberUserId,
        string $role
    ): void {
        $currentUserId =
            $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can change member access.'
            );
        }

        if ($currentUserId === $memberUserId)
        {
            throw new RuntimeException(
                'The owner cannot change their own access level.'
            );
        }

        $role = strtolower(trim($role));

        if (!in_array(
            $role,
            ['editor', 'viewer'],
            true
        ))
        {
            throw new RuntimeException(
                'Invalid member role.'
            );
        }

        $member =
            $this->healthProfileRepository
                ->findMember(
                    $profileId,
                    $memberUserId
                );

        if ($member === null)
        {
            throw new RuntimeException(
                'Health profile member not found.'
            );
        }

        if ($member['status'] !== 'active')
        {
            throw new RuntimeException(
                'Only active members can have their role changed.'
            );
        }

        if ($member['role'] === 'owner')
        {
            throw new RuntimeException(
                'The owner role cannot be changed.'
            );
        }

        if ($member['role'] === $role)
        {
            return;
        }

        $updated =
            $this->healthProfileRepository
                ->updateMemberRole(
                    $profileId,
                    $memberUserId,
                    $role
                );

        if (!$updated)
        {
            throw new RuntimeException(
                'Unable to update member access.'
            );
        }
    }

    /**
     * Revoke a member's access to a health profile.
     */
    public function revokeMember(
        int $profileId,
        int $memberUserId
    ): void {
        $currentUserId =
            $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can revoke member access.'
            );
        }

        if ($currentUserId === $memberUserId)
        {
            throw new RuntimeException(
                'The owner cannot revoke their own access.'
            );
        }

        $member =
            $this->healthProfileRepository
                ->findMember(
                    $profileId,
                    $memberUserId
                );

        if ($member === null)
        {
            throw new RuntimeException(
                'Health profile member not found.'
            );
        }

        if ($member['status'] !== 'active')
        {
            throw new RuntimeException(
                'This member does not currently have active access.'
            );
        }

        if ($member['role'] === 'owner')
        {
            throw new RuntimeException(
                'The owner cannot be revoked.'
            );
        }

        $revoked =
            $this->healthProfileRepository
                ->revokeMember(
                    $profileId,
                    $memberUserId
                );

        if (!$revoked)
        {
            throw new RuntimeException(
                'Unable to revoke member access.'
            );
        }
    }


    /**
     * Reactivate a previously revoked member.
     */
    public function reactivateMember(
        int $profileId,
        int $memberUserId,
        string $role
    ): void {
        $currentUserId =
            $this->authenticatedUserId();

        $profile =
            $this->healthProfileService
                ->getProfile($profileId);

        if (($profile['role'] ?? null) !== 'owner')
        {
            throw new RuntimeException(
                'Only the health profile owner can restore member access.'
            );
        }

        if ($currentUserId === $memberUserId)
        {
            throw new RuntimeException(
                'The owner cannot modify their own access.'
            );
        }

        $role = strtolower(trim($role));

        if (!in_array(
            $role,
            ['editor', 'viewer'],
            true
        ))
        {
            throw new RuntimeException(
                'Invalid member role.'
            );
        }

        $member =
            $this->healthProfileRepository
                ->findMember(
                    $profileId,
                    $memberUserId
                );

        if ($member === null)
        {
            throw new RuntimeException(
                'Health profile member not found.'
            );
        }

        if ($member['status'] !== 'revoked')
        {
            throw new RuntimeException(
                'Only revoked members can be restored.'
            );
        }

        if ($member['role'] === 'owner')
        {
            throw new RuntimeException(
                'The owner role cannot be restored through this operation.'
            );
        }

        $reactivated =
            $this->healthProfileRepository
                ->reactivateMember(
                    $profileId,
                    $memberUserId,
                    $role
                );

        if (!$reactivated)
        {
            throw new RuntimeException(
                'Unable to restore member access.'
            );
        }
    }
}