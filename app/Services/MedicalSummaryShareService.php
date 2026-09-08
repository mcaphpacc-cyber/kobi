<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MedicalSummaryShareRepository;
use RuntimeException;

class MedicalSummaryShareService
{
    private MedicalSummaryShareRepository $repository;
    private MedicalSummaryService $medicalSummaryService;
    private HealthProfileService $healthProfileService;
    private AuthService $authService;


    public function __construct(
        MedicalSummaryShareRepository $repository,
        MedicalSummaryService $medicalSummaryService,
        HealthProfileService $healthProfileService,
        AuthService $authService
    ) {
        $this->repository =
            $repository;

        $this->medicalSummaryService =
            $medicalSummaryService;

        $this->healthProfileService =
            $healthProfileService;

        $this->authService =
            $authService;
    }


    /**
     * Create a new Medical Summary share.
     *
     * @return array{
     *     id: int,
     *     token: string,
     *     expires_at: string
     * }
     */
    public function createShare(
        int $profileId,
        int $expiresInDays
    ): array {
        $userId =
            $this->authService->id();

        if (!$userId) {
            throw new RuntimeException(
                'You must be logged in to share a medical summary.'
            );
        }


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


        if (
            $expiresInDays < 1 ||
            $expiresInDays > 30
        ) {
            throw new RuntimeException(
                'Medical Summary share expiry must be between 1 and 30 days.'
            );
        }


        $expiresAt =
            new \DateTimeImmutable(
                'now'
            );

        $expiresAt =
            $expiresAt->modify(
                '+' . $expiresInDays . ' days'
            );


        /*
         * Generate a cryptographically secure
         * random access token.
         */
        $token =
            bin2hex(
                random_bytes(32)
            );


        /*
         * Store only the SHA-256 hash.
         */
        $tokenHash =
            hash(
                'sha256',
                $token
            );


        /*
         * Generate the current Medical Summary
         * and store it as an immutable snapshot.
         */
        $summary =
            $this->medicalSummaryService
                ->getSummary($profileId);
        
                /*
         * Freeze the patient's age inside the share snapshot.
         *
         * The normal Medical Summary may derive age dynamically,
         * but a shared snapshot must remain unchanged after creation.
         */
        $dob =
            trim(
                (string) (
                    $summary['profile']['date_of_birth']
                    ?? ''
                )
            );

        if ($dob !== '') {

            try {

                $birthDate =
                    new \DateTime($dob);

                $today =
                    new \DateTime('today');

                $summary['profile']['age'] =
                    $birthDate->diff($today)->y;

            }
            catch (\Throwable $exception) {

                $summary['profile']['age'] =
                    null;
            }
        }
        else {

            $summary['profile']['age'] =
                null;
        }


        $snapshot =
            json_encode(
                $summary,
                JSON_THROW_ON_ERROR |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );


        $shareId =
            $this->repository
                ->create(
                    [
                        'health_profile_id' =>
                            $profileId,

                        'created_by' =>
                            $userId,

                        'token_hash' =>
                            $tokenHash,

                        'snapshot' =>
                            $snapshot,

                        'snapshot_schema_version' =>
                            '1.0',

                        'snapshot_created_at' =>
                            date('Y-m-d H:i:s'),

                        'expires_at' =>
                            $expiresAt
                                ->format(
                                    'Y-m-d H:i:s'
                                ),

                        'status' =>
                            'active'
                    ]
                );


        return [
            'id' =>
                $shareId,

            'token' =>
                $token,

            'expires_at' =>
                $expiresAt
                    ->format(
                        'Y-m-d H:i:s'
                    )
        ];
    }


    /**
     * Get all Medical Summary shares
     * for a health profile.
     */
    public function getShares(
        int $profileId
    ): array {
        $profile =
            $this->healthProfileService
                ->getProfile($profileId);


        if (
            ($profile['role'] ?? null)
            !== 'owner'
        ) {
            throw new RuntimeException(
                'Only the health profile owner can manage medical summary shares.'
            );
        }


        $shares =
            $this->repository
                ->findByProfileId($profileId);


        $now =
            new \DateTimeImmutable(
                'now'
            );


        foreach ($shares as &$share) {

            $expiresAt =
                new \DateTimeImmutable(
                    $share['expires_at']
                );

            $share['is_expired'] =
                $expiresAt <= $now;

            $share['is_active'] =
                $share['status'] === 'active'
                &&
                !$share['is_expired'];
        }

        unset($share);


        return $shares;
    }


    /**
     * Revoke a Medical Summary share.
     */
    public function revokeShare(
        int $shareId
    ): void {
        $userId =
            $this->authService->id();

        if (!$userId) {
            throw new RuntimeException(
                'You must be logged in to revoke a medical summary share.'
            );
        }


        $share =
            $this->repository
                ->findById($shareId);


        if (!$share) {
            throw new RuntimeException(
                'Medical Summary share not found.'
            );
        }


        $profile =
            $this->healthProfileService
                ->getProfile(
                    (int) $share['health_profile_id']
                );


        if (
            ($profile['role'] ?? null)
            !== 'owner'
        ) {
            throw new RuntimeException(
                'Only the health profile owner can revoke a medical summary share.'
            );
        }


        if (
            $this->repository
                ->revoke(
                    $shareId,
                    $userId
                ) === false
        ) {
            throw new RuntimeException(
                'Unable to revoke the Medical Summary share.'
            );
        }
    }


    /**
     * Retrieve and validate a public Medical Summary share.
     */
    public function getPublicShare(
        string $token
    ): array {
        $token =
            trim($token);


        if ($token === '') {
            throw new RuntimeException(
                'Invalid Medical Summary share.'
            );
        }


        /*
         * The public token is never used directly
         * in the database query.
         */
        $tokenHash =
            hash(
                'sha256',
                $token
            );


        $share =
            $this->repository
                ->findByTokenHash(
                    $tokenHash
                );


        if (!$share) {
            throw new RuntimeException(
                'Medical Summary share not found.'
            );
        }


        if (
            $share['status']
            !== 'active'
        ) {
            throw new RuntimeException(
                'This Medical Summary share has been revoked.'
            );
        }


        $expiresAt =
            new \DateTimeImmutable(
                $share['expires_at']
            );


        $now =
            new \DateTimeImmutable(
                'now'
            );


        if ($expiresAt <= $now) {
            throw new RuntimeException(
                'This Medical Summary share has expired.'
            );
        }


        $snapshot =
            json_decode(
                $share['snapshot'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );


        if (!is_array($snapshot)) {
            throw new RuntimeException(
                'Unable to read the Medical Summary share.'
            );
        }


        /*
         * Log only after all validation succeeds.
         */
        $ipAddress =
            trim(
                (string) (
                    $_SERVER['REMOTE_ADDR']
                    ?? ''
                )
            );

        $userAgent =
            trim(
                (string) (
                    $_SERVER['HTTP_USER_AGENT']
                    ?? ''
                )
            );

        if ($userAgent !== '') {
            $userAgent =
                mb_substr(
                    $userAgent,
                    0,
                    1000
                );
        }
        else {
            $userAgent = null;
        }

        $this->repository
            ->logAccess(
                (int) $share['id'],
                $ipAddress !== ''
                    ? $ipAddress
                    : null,
                $userAgent
            );


        return [
            'share' =>
                $share,

            'snapshot' =>
                $snapshot
        ];
    }
}