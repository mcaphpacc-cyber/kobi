<?php

declare(strict_types=1);

namespace App\Repositories;

class UserAuthIdentityRepository extends BaseRepository
{
    /**
     * Find an authentication identity by provider
     * and provider-specific user ID.
     */
    public function findByProviderUserId(
        string $provider,
        string $providerUserId
    ): ?array {
        return $this->fetch(
            "
            SELECT
                id,
                user_id,
                provider,
                provider_user_id,
                provider_email,
                created_at,
                updated_at
            FROM user_auth_identities
            WHERE
                provider = :provider
                AND provider_user_id = :provider_user_id
            LIMIT 1
            ",
            [
                'provider' => trim($provider),
                'provider_user_id' => trim($providerUserId)
            ]
        );
    }

    /**
     * Find an authentication identity by provider
     * and provider email address.
     *
     * This is useful for controlled account-linking
     * during the OAuth flow.
     */
    public function findByProviderEmail(
        string $provider,
        string $providerEmail
    ): ?array {
        return $this->fetch(
            "
            SELECT
                id,
                user_id,
                provider,
                provider_user_id,
                provider_email,
                created_at,
                updated_at
            FROM user_auth_identities
            WHERE
                provider = :provider
                AND provider_email = :provider_email
            LIMIT 1
            ",
            [
                'provider' => trim($provider),
                'provider_email' => strtolower(
                    trim($providerEmail)
                )
            ]
        );
    }

    /**
     * Find all authentication identities belonging
     * to a KOBI user.
     */
    public function findByUserId(
        int $userId
    ): array {
        return $this->fetchAll(
            "
            SELECT
                id,
                user_id,
                provider,
                provider_user_id,
                provider_email,
                created_at,
                updated_at
            FROM user_auth_identities
            WHERE user_id = :user_id
            ORDER BY id ASC
            ",
            [
                'user_id' => $userId
            ]
        );
    }

    /**
     * Create an authentication identity.
     */
    public function create(
        int $userId,
        string $provider,
        string $providerUserId,
        ?string $providerEmail = null
    ): int {
        $this->query(
            "
            INSERT INTO user_auth_identities (
                user_id,
                provider,
                provider_user_id,
                provider_email
            )
            VALUES (
                :user_id,
                :provider,
                :provider_user_id,
                :provider_email
            )
            ",
            [
                'user_id' => $userId,
                'provider' => trim($provider),
                'provider_user_id' => trim($providerUserId),
                'provider_email' =>
                    $providerEmail !== null
                        ? strtolower(trim($providerEmail))
                        : null
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Update the provider email stored for an
     * existing authentication identity.
     */
    public function updateProviderEmail(
        int $identityId,
        ?string $providerEmail
    ): bool {
        return $this->execute(
            "
            UPDATE user_auth_identities
            SET provider_email = :provider_email
            WHERE id = :id
            ",
            [
                'id' => $identityId,
                'provider_email' =>
                    $providerEmail !== null
                        ? strtolower(trim($providerEmail))
                        : null
            ]
        );
    }

    /**
     * Remove an authentication identity.
     */
    public function delete(
        int $identityId
    ): bool {
        return $this->execute(
            "
            DELETE FROM user_auth_identities
            WHERE id = :id
            ",
            [
                'id' => $identityId
            ]
        );
    }
}