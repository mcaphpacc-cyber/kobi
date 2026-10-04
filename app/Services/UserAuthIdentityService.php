<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserAuthIdentityRepository;
use RuntimeException;
use App\Repositories\UserRepository;

class UserAuthIdentityService
{
    public function __construct(
        private UserAuthIdentityRepository $repository,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Find a linked authentication identity.
     */
    public function findByProviderUserId(
        string $provider,
        string $providerUserId
    ): ?array {
        $provider = strtolower(trim($provider));
        $providerUserId = trim($providerUserId);

        if (
            $provider === '' ||
            $providerUserId === ''
        ) {
            return null;
        }

        return $this->repository->findByProviderUserId(
            $provider,
            $providerUserId
        );
    }

    /**
     * Find an authentication identity by provider email.
     *
     * This is a lookup only. It does not automatically
     * link the identity to an existing KOBI account.
     */
    public function findByProviderEmail(
        string $provider,
        string $providerEmail
    ): ?array {
        $provider = strtolower(trim($provider));
        $providerEmail = strtolower(trim($providerEmail));

        if (
            $provider === '' ||
            $providerEmail === ''
        ) {
            return null;
        }

        return $this->repository->findByProviderEmail(
            $provider,
            $providerEmail
        );
    }

    /**
     * Return all authentication identities
     * belonging to a KOBI user.
     */
    public function getForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        return $this->repository->findByUserId(
            $userId
        );
    }

    /**
     * Link an external authentication identity
     * to a KOBI user.
     */
    public function link(
        int $userId,
        string $provider,
        string $providerUserId,
        ?string $providerEmail = null
    ): int {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        $provider = strtolower(trim($provider));
        $providerUserId = trim($providerUserId);

        $providerEmail =
            $providerEmail !== null
                ? strtolower(trim($providerEmail))
                : null;

        if ($provider === '') {
            throw new RuntimeException(
                'Authentication provider is required.'
            );
        }

        if ($providerUserId === '') {
            throw new RuntimeException(
                'Provider user ID is required.'
            );
        }

        /*
         * Never allow the same external identity
         * to be attached to another KOBI account.
         */
        $existing = $this->repository
            ->findByProviderUserId(
                $provider,
                $providerUserId
            );

        if ($existing) {
            if (
                (int) $existing['user_id'] === $userId
            ) {
                /*
                 * The identity is already linked to
                 * this KOBI account.
                 *
                 * Update the provider email if necessary.
                 */
                if (
                    $providerEmail !==
                    strtolower(
                        (string) (
                            $existing['provider_email']
                            ?? ''
                        )
                    )
                ) {
                    $this->repository
                        ->updateProviderEmail(
                            (int) $existing['id'],
                            $providerEmail
                        );
                }

                return (int) $existing['id'];
            }

            throw new RuntimeException(
                'This authentication account is already linked to another KOBI account.'
            );
        }

        return $this->repository->create(
            $userId,
            $provider,
            $providerUserId,
            $providerEmail
        );
    }

    /**
     * Unlink an authentication identity.
     *
     * The caller must enforce the appropriate account
     * security rules before allowing this operation.
     */
    public function unlink(
        int $userId,
        int $identityId
    ): bool {
        if ($userId <= 0 || $identityId <= 0) {
            throw new RuntimeException(
                'Invalid authentication identity.'
            );
        }

        $identities = $this->repository->findByUserId(
            $userId
        );

        $owned = false;

        foreach ($identities as $identity) {
            if (
                (int) $identity['id'] === $identityId
            ) {
                $owned = true;
                break;
            }
        }

        if (!$owned) {
            throw new RuntimeException(
                'Authentication identity not found.'
            );
        }

        $hasPassword =
            $this->userRepository->hasPassword(
                $userId
            );

        /*
        * Do not allow the user to remove the final
        * usable authentication method.
        */
        if (
            !$hasPassword &&
            count($identities) <= 1
        ) {
            throw new RuntimeException(
                'You cannot disconnect your only authentication method. Please set a KOBI password first.'
            );
        }

        return $this->repository->delete(
            $identityId
        );
    }

    /**
     * Determine whether the KOBI user has
     * an established password authentication method.
     */
    public function hasPassword(
        int $userId
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        return $this->userRepository->hasPassword(
            $userId
        );
    }

}