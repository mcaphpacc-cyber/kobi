<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserAuthTokenRepository;
use RuntimeException;

class UserAuthTokenService
{
    private const TYPE_EMAIL_VERIFICATION = 'email_verification';
    private const TYPE_PASSWORD_RESET = 'password_reset';

    /*
     * Email verification links remain valid for 24 hours.
     */
    private const EMAIL_VERIFICATION_TTL = 86400;

    /*
     * Password reset links remain valid for 1 hour.
     */
    private const PASSWORD_RESET_TTL = 3600;

    public function __construct(
        private UserAuthTokenRepository $repository
    ) {
    }

    /**
     * Create a fresh email verification token.
     *
     * A previous unused verification token is invalidated first.
     */
    public function createEmailVerificationToken(
        int $userId
    ): string {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        if (!$this->canResendEmailVerification($userId)) {
            throw new RuntimeException(
                'Please wait before requesting another verification email.'
            );
        }

        $this->repository->invalidateUnusedTokens(
            $userId,
            self::TYPE_EMAIL_VERIFICATION
        );

        return $this->createToken(
            $userId,
            self::TYPE_EMAIL_VERIFICATION,
            self::EMAIL_VERIFICATION_TTL
        );
    }

    /**
     * Create a password reset token.
     *
     * Any previously issued unused password reset tokens
     * for the same user are invalidated first.
     *
     * The raw token is returned only once.
     */
    public function createPasswordResetToken(
        int $userId
    ): string {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        $this->repository->invalidateUnusedTokens(
            $userId,
            self::TYPE_PASSWORD_RESET
        );

        return $this->createToken(
            $userId,
            self::TYPE_PASSWORD_RESET,
            self::PASSWORD_RESET_TTL
        );
    }

    /**
     * Validate an email verification token.
     *
     * Returns the token record when valid.
     */
    public function validateEmailVerificationToken(
        string $token
    ): array {
        return $this->validateToken(
            $token,
            self::TYPE_EMAIL_VERIFICATION
        );
    }

    /**
     * Invalidate outstanding reset links after a successful password update.
     * Having no unused tokens is a successful no-op.
     */
    public function invalidateUnusedPasswordResetTokens(int $userId): void
    {
        if ($userId <= 0) {
            throw new RuntimeException('Invalid user account.');
        }

        $this->repository->invalidateUnusedTokens(
            $userId,
            self::TYPE_PASSWORD_RESET
        );
    }

    /**
     * Validate a password reset token.
     *
     * Returns the token record when valid.
     */
    public function validatePasswordResetToken(
        string $token
    ): array {
        return $this->validateToken(
            $token,
            self::TYPE_PASSWORD_RESET
        );
    }

    /**
     * Consume an email verification token.
     */
    public function consumeEmailVerificationToken(
        string $token
    ): array {
        return $this->consumeToken(
            $token,
            self::TYPE_EMAIL_VERIFICATION
        );
    }

    /**
     * Consume a password reset token.
     */
    public function consumePasswordResetToken(
        string $token
    ): array {
        return $this->consumeToken(
            $token,
            self::TYPE_PASSWORD_RESET
        );
    }

    /**
     * Create a secure authentication token.
     *
     * The raw token is returned to the caller.
     * Only its SHA-256 hash is stored in the database.
     */
    private function createToken(
        int $userId,
        string $type,
        int $ttl
    ): string {
        try {
            $rawToken = bin2hex(
                random_bytes(32)
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Unable to create a secure authentication token.',
                0,
                $exception
            );
        }

        $tokenHash = hash(
            'sha256',
            $rawToken
        );

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + $ttl
        );

        $this->repository->create(
            $userId,
            $tokenHash,
            $type,
            $expiresAt
        );

        return $rawToken;
    }

    /**
     * Validate a raw token against its expected type.
     */
    private function validateToken(
        string $token,
        string $type
    ): array {
        $token = trim($token);

        if ($token === '') {
            throw new RuntimeException(
                'Invalid or expired token.'
            );
        }

        /*
         * Tokens generated by createToken() are 64 hexadecimal
         * characters. Reject malformed values before querying
         * the database.
         */
        if (
            strlen($token) !== 64 ||
            !ctype_xdigit($token)
        ) {
            throw new RuntimeException(
                'Invalid or expired token.'
            );
        }

        $tokenHash = hash(
            'sha256',
            $token
        );

        $record = $this->repository->findValidToken(
            $tokenHash,
            $type
        );

        if ($record === null) {
            throw new RuntimeException(
                'Invalid or expired token.'
            );
        }

        return $record;
    }

    /**
     * Validate and consume a token atomically from the
     * application flow.
     *
     * The returned record contains the associated user ID.
     */
    private function consumeToken(
        string $token,
        string $type
    ): array {
        $record = $this->validateToken(
            $token,
            $type
        );

        $used = $this->repository->markUsed(
            (int) $record['id']
        );

        if (!$used) {
            throw new RuntimeException(
                'Invalid or expired token.'
            );
        }

        return $record;
    }

    /**
     * Determine whether a new verification email can be sent.
     */
    public function canResendEmailVerification(
        int $userId
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        return !$this->repository->hasRecentToken(
            $userId,
            self::TYPE_EMAIL_VERIFICATION,
            60
        );
    }

    
}
