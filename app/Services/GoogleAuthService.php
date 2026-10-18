<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client as GoogleClient;
use RuntimeException;

class GoogleAuthService
{
    private ?GoogleClient $client = null;

    /**
     * Initialize inside the controller action's error boundary, not during DI resolution.
     */
    private function client(): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $clientId = trim(
            (string) env('GOOGLE_CLIENT_ID', '')
        );

        $clientSecret = trim(
            (string) env('GOOGLE_CLIENT_SECRET', '')
        );

        $redirectUri = trim(
            (string) env('GOOGLE_REDIRECT_URI', '')
        );

        if (
            $clientId === '' ||
            $clientSecret === '' ||
            $redirectUri === ''
        ) {
            throw new RuntimeException(
                'Google authentication is not configured.'
            );
        }

        $client = new GoogleClient();

        $client->setClientId(
            $clientId
        );

        $client->setClientSecret(
            $clientSecret
        );

        $client->setRedirectUri(
            $redirectUri
        );

        $client->setAccessType(
            'offline'
        );

        $client->setPrompt(
            'select_account'
        );

        $client->setScopes([
            'openid',
            'email',
            'profile'
        ]);

        return $this->client = $client;
    }

    /**
     * Generate Google's authorization URL.
     */
    public function getAuthorizationUrl(
        string $state
    ): string {
        $state = trim($state);

        if ($state === '') {
            throw new RuntimeException(
                'Invalid Google authentication state.'
            );
        }

        $client = $this->client();
        $client->setState($state);

        return $client
            ->createAuthUrl();
    }

    /**
     * Exchange Google's authorization code
     * for an authenticated Google identity.
     *
     * @return array{
     *     provider_user_id: string,
     *     email: string,
     *     name: string,
     *     picture: ?string,
     *     email_verified: bool
     * }
     */
    public function authenticate(
        string $code
    ): array {
        $code = trim($code);

        if ($code === '') {
            throw new RuntimeException(
                'Google authentication code is missing.'
            );
        }

        $client = $this->client();
        $token = $client
            ->fetchAccessTokenWithAuthCode(
                $code
            );

        if (
            isset($token['error']) ||
            !is_array($token)
        ) {
            throw new RuntimeException(
                'Unable to authenticate with Google.'
            );
        }

        $accessToken = $token['access_token']
            ?? null;

        if (
            !is_string($accessToken) ||
            $accessToken === ''
        ) {
            throw new RuntimeException(
                'Unable to authenticate with Google.'
            );
        }

        $client->setAccessToken(
            $token
        );

        /*
         * Verify the Google ID token when Google
         * has returned one.
         */
        $idToken = $token['id_token'] ?? null;

        if (
            !is_string($idToken) ||
            $idToken === ''
        ) {
            throw new RuntimeException(
                'Google did not return a valid identity token.'
            );
        }

        $payload = $client
            ->verifyIdToken($idToken);

        if (!is_array($payload)) {
            throw new RuntimeException(
                'Unable to verify your Google identity.'
            );
        }

        /*
         * Google account ID.
         *
         * This is the stable provider identity that
         * must be stored in user_auth_identities.
         */
        $providerUserId = trim(
            (string) (
                $payload['sub'] ?? ''
            )
        );

        /*
         * Google email.
         */
        $email = strtolower(
            trim(
                (string) (
                    $payload['email'] ?? ''
                )
            )
        );

        /*
         * Google explicitly tells us whether the
         * email address has been verified.
         */
        $emailVerified =
            ($payload['email_verified'] ?? false)
            === true;

        if (
            $providerUserId === '' ||
            $email === ''
        ) {
            throw new RuntimeException(
                'Google did not return a valid account identity.'
            );
        }

        if (!$emailVerified) {
            throw new RuntimeException(
                'Your Google email address must be verified before you can use Google sign-in.'
            );
        }

        $name = trim(
            (string) (
                $payload['name']
                ?? ''
            )
        );

        $picture = null;

        if (
            isset($payload['picture']) &&
            is_string($payload['picture'])
        ) {
            $picture = trim(
                $payload['picture']
            );

            if ($picture === '') {
                $picture = null;
            }
        }

        return [
            'provider_user_id' => $providerUserId,
            'email'            => $email,
            'name'             => $name,
            'picture'          => $picture,
            'email_verified'   => true
        ];
    }
}
