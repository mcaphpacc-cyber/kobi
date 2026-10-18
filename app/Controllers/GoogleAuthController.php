<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\Logger;
use App\Services\AuthService;
use App\Services\GoogleAuthService;
use App\Services\GoogleOAuthStateService;
use App\Services\UserAuthIdentityService;
use RuntimeException;
use Throwable;
use App\Services\HealthProfileService;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $googleAuthService,
        private GoogleOAuthStateService $stateService,
        private UserAuthIdentityService $identityService,
        private AuthService $authService,
        private HealthProfileService $healthProfileService,
        private Session $session
    ) {
    }

    /**
     * Redirect the user to Google.
     */
    public function redirect(): void
    {
        try {
            $state = $this->stateService->generate();

            $authorizationUrl =
                $this->googleAuthService
                    ->getAuthorizationUrl($state);

            header(
                'Location: ' . $authorizationUrl
            );

            exit;

        } catch (Throwable $exception) {
            $this->handleError($exception, 'login');
        }
    }

    public function link(): void
    {
        if (!$this->authService->check()) {
            redirect('/login');
        }

        try {
            $state = $this->stateService->generate(
                'link'
            );

            $authorizationUrl =
                $this->googleAuthService
                    ->getAuthorizationUrl($state);

            header(
                'Location: ' . $authorizationUrl
            );

            exit;

        } catch (Throwable $exception) {
            $this->handleError($exception, 'link');
        }
    }

    /**
     * Handle Google's OAuth callback.
     */
    public function callback(): void
    {
        $intent = $this->stateService->intent();

        try {
            // Validate and consume state even when Google returns an OAuth error.
            $state = $_GET['state'] ?? null;
            $intent = $this->stateService->validate(
                is_string($state) ? $state : null
            );

            /*
             * Google may return an OAuth error instead
             * of an authorization code.
             */
            $error = $_GET['error'] ?? null;

            if (
                is_string($error) &&
                trim($error) !== ''
            ) {
                throw new RuntimeException(
                    'Google sign-in was cancelled or could not be completed.'
                );
            }

            $code = $_GET['code'] ?? '';

            if (!is_string($code)) {
                $code = '';
            }

            /*
             * Authenticate with Google and obtain the
             * verified external identity.
             */
            $googleUser =
                $this->googleAuthService
                    ->authenticate($code);

            if ($intent === 'link') {
                $this->handleGoogleLink(
                    $googleUser
                );

                return;
            }

            $provider = 'google';

            /*
             * First check whether this exact Google
             * identity is already linked to KOBI.
             */
            $identity =
                $this->identityService
                    ->findByProviderUserId(
                        $provider,
                        $googleUser['provider_user_id']
                    );

            if ($identity) {

                /*
                 * The Google identity already belongs
                 * to a KOBI account.
                 */
                $this->authenticateExistingUser(
                    (int) $identity['user_id']
                );

                return;
            }

            /*
             * The Google identity is new.
             *
             * IMPORTANT:
             * Do not automatically link it merely
             * because the email matches an existing
             * KOBI account.
             */
            $this->handleNewGoogleIdentity(
                $googleUser
            );

        } catch (Throwable $exception) {
            $this->handleError($exception, $intent);
        }
    }

    private function handleError(Throwable $exception, string $intent): void
    {
        $this->stateService->clear();

        // Record diagnostic location without logging OAuth tokens, secrets or arguments.
        Logger::error(sprintf(
            'Google OAuth %s failed: %s (code %s) at %s:%d',
            $intent,
            get_class($exception),
            (string) $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        ));

        $safeMessages = [
            'Google sign-in was cancelled or could not be completed.',
            'Invalid Google authentication request.',
            'Google authentication code is missing.',
            'Your Google email address must be verified before you can use Google sign-in.',
            'A KOBI account already exists with this Google email address. Please sign in to your KOBI account first. Google linking will be available from your account settings.',
            'This Google account is already linked to another KOBI account.',
            'This authentication account is already linked to another KOBI account.',
            'Your KOBI session has expired. Please sign in again.',
            'This account is currently inactive.',
            'Please verify your email address before continuing.'
        ];

        $message = $intent === 'link'
            ? 'Unable to connect Google. Please try again.'
            : 'Unable to sign in with Google. Please try again.';

        if (
            get_class($exception) === RuntimeException::class &&
            in_array($exception->getMessage(), $safeMessages, true)
        ) {
            $message = $exception->getMessage();
        }

        if ($intent === 'link' && $this->authService->check()) {
            $this->session->put('google_auth_error', $message);
            redirect('/account');
        }

        $this->view('auth/login', [
            'title' => 'Login',
            'error' => $message
        ]);
    }

    /**
     * Authenticate an existing KOBI account through
     * the shared authentication/session mechanism.
     */
    private function authenticateExistingUser(
        int $userId
    ): void {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Unable to authenticate the KOBI account.'
            );
        }

        /*
         * We will add the provider-independent
         * authenticated-session method to AuthService
         * before enabling this path.
         */
        $this->authService
            ->loginById(
                $userId
            );

        $this->redirectAfterAuthentication();
    }

    /**
     * Handle a Google identity that is not yet linked
     * to a KOBI account.
     */
    private function handleNewGoogleIdentity(
        array $googleUser
    ): void {
        try {
            $user = $this->authService->createGoogleUser(
                (string) $googleUser['name'],
                (string) $googleUser['email']
            );
        } catch (RuntimeException $exception) {

            /*
            * A matching KOBI email must never be
            * automatically linked to Google.
            */
            if (
                $exception->getMessage() ===
                'A KOBI account with this email address already exists. Please sign in to that account first.'
            ) {
                throw new RuntimeException(
                    'A KOBI account already exists with this Google email address. Please sign in to your KOBI account first. Google linking will be available from your account settings.'
                );
            }

            throw $exception;
        }

        $userId = (int) $user['id'];

        if ($userId <= 0) {
            throw new RuntimeException(
                'Unable to create the KOBI account.'
            );
        }

        /*
        * Link the verified Google identity to the
        * newly created KOBI account.
        */
        $this->identityService->link(
            $userId,
            'google',
            (string) $googleUser['provider_user_id'],
            (string) $googleUser['email']
        );

        /*
        * Authenticate the newly created account.
        */
        $this->authService->loginById(
            $userId
        );

        /*
        * Normal Google registrations receive their own
        * health profile automatically.
        *
        * Invitation-based authentication continues through
        * the existing pending-invitation flow.
        */
        if (!$this->hasPendingHealthProfileInvitation()) {
            $this->healthProfileService
                ->ensureSelfProfile();
        }

        $this->redirectAfterAuthentication();
    }

    

    private function hasPendingHealthProfileInvitation(): bool
    {
        $token = $this->session->get(
            'pending_health_profile_invitation'
        );

        return is_string($token) && $token !== '';
    }

    /**
     * Redirect an authenticated user to any pending
     * health-profile invitation, otherwise home.
     */
    private function redirectAfterAuthentication(): void
    {
        $token = $this->session->get(
            'pending_health_profile_invitation'
        );

        if (
            is_string($token) &&
            $token !== ''
        ) {
            $this->session->remove(
                'pending_health_profile_invitation'
            );

            redirect(
                '/shared/health-profile-invitation/' .
                urlencode($token)
            );
        }

        redirect('/');
    }

    private function handleGoogleLink(
        array $googleUser
    ): void {
        if (!$this->authService->check()) {
            throw new RuntimeException(
                'Your KOBI session has expired. Please sign in again.'
            );
        }

        $currentUser = $this->authService->user();

        if (!$currentUser) {
            throw new RuntimeException(
                'Unable to identify your KOBI account.'
            );
        }

        $userId = (int) $currentUser['id'];

        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid KOBI account.'
            );
        }

        $provider = 'google';

        $identity =
            $this->identityService
                ->findByProviderUserId(
                    $provider,
                    (string) $googleUser['provider_user_id']
                );

        /*
        * This Google identity is already linked.
        */
        if ($identity) {

            if ((int) $identity['user_id'] === $userId) {
                redirect(
                    '/account?google_already_linked=1'
                );
            }

            /*
            * Never allow a Google identity belonging
            * to another KOBI account to be transferred.
            */
            throw new RuntimeException(
                'This Google account is already linked to another KOBI account.'
            );
        }

        /*
        * Explicitly link the verified Google identity
        * to the currently authenticated KOBI account.
        */
        $this->identityService->link(
            $userId,
            $provider,
            (string) $googleUser['provider_user_id'],
            (string) $googleUser['email']
        );

        redirect(
            '/account?google_linked=1'
        );
    }
}
