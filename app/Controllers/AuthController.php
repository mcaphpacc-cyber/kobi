<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;
use RuntimeException;
use App\Services\HealthProfileService;
use App\Services\MailService;
use App\Services\UserAuthTokenService;
use App\Services\EmailVerificationService;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private HealthProfileService $healthProfileService,
        private EmailVerificationService $emailVerificationService,
        private UserAuthTokenService $userAuthTokenService,
        private MailService $mailService,
        private Request $request,
        private Session $session
    ) {
    }

    /**
     * Display the registration form.
     */
    public function register(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        $this->view(
            'auth/register',
            [
                'title' => 'Create Account'
            ]
        );
    }

    /**
     * Process registration.
     */
    public function store(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        try {
            $this->verifyCsrf();

            $user = $this->authService->register(
                (string) $this->request->post('name', ''),
                (string) $this->request->post('email', ''),
                (string) $this->request->post('password', '')
            );

            $this->emailVerificationService->send(
                (int) $user['id']
            );

            /*
            * Do not authenticate the user yet.
            *
            * The account must be verified first.
            */
            $this->view(
                'auth/verification-sent',
                [
                    'title' => 'Verify Your Email',
                    'email' => (string) $user['email']
                ]
            );

        } catch (RuntimeException $exception) {

            $this->view(
                'auth/register',
                [
                    'title' => 'Create Account',
                    'error' => $exception->getMessage(),
                    'old' => [
                        'name' => (string) $this->request->post(
                            'name',
                            ''
                        ),
                        'email' => (string) $this->request->post(
                            'email',
                            ''
                        )
                    ]
                ]
            );
        }
    }

    /**
     * Display the login form.
     */
    public function login(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        $this->view(
                'auth/login',
                [
                    'title' => 'Login'
                ]
            );
    }

    /**
     * Process login.
     */
    public function authenticate(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        try {
            $this->verifyCsrf();
            $this->authService->login(
                (string) $this->request->post('email', ''),
                (string) $this->request->post('password', '')
            );

            $this->redirectAfterAuthentication();

        } catch (RuntimeException $exception) {

            $message = $exception->getMessage();

            $this->view(
                'auth/login',
                [
                    'title' => 'Login',
                    'error' => $message,
                    'verification_required' =>
                        $message ===
                        'Please verify your email address before logging in.',
                    'old' => [
                        'email' => (string) $this->request->post(
                            'email',
                            ''
                        )
                    ]
                ]
            );
        }
    }

    /**
     * Logout the current user.
     */
    public function logout(): void
    {
        try {

            $this->verifyCsrf();

            $this->authService->logout();

            redirect('/');

        } catch (RuntimeException $exception) {

            abort(
                403,
                $exception->getMessage()
            );
        }
    }

    /**
     * Determine whether the current registration
     * was initiated through a health profile invitation.
     */
    private function hasPendingHealthProfileInvitation(): bool
    {
        $token =
            $this->session->get(
                'pending_health_profile_invitation'
            );

        return is_string($token) && $token !== '';
    }

    private function verifyCsrf(): void
    {
        $token = $this->request->post(
            '_csrf_token',
            null
        );

        if (
            !$this->session->verifyCsrfToken(
                is_string($token) ? $token : null
            )
        ) {
            throw new RuntimeException(
                'Invalid security token. Please try again.'
            );
        }
    }

    /**
     * Redirect the authenticated user to a pending
     * health profile invitation when one exists.
     */
    private function redirectAfterAuthentication(): void
    {
        $token =
            $this->session->get(
                'pending_health_profile_invitation'
            );

        if (
            is_string($token) &&
            $token !== ''
        )
        {
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


    /**
     * Verify a user's email address.
     */
    public function verifyEmail(): void
    {
        $token = (string) $this->request->get(
            'token',
            ''
        );

        try {
            $record = $this->userAuthTokenService
                ->consumeEmailVerificationToken($token);

            $this->authService->verifyEmailAddress(
                (int) $record['user_id']
            );

            /*
            * Authenticate the user after successful verification.
            */
            $this->authService->loginById(
                (int) $record['user_id']
            );

            /*
            * Normal registrations receive their own health profile.
            *
            * Invitation-based registrations continue through the
            * existing pending-invitation flow.
            */
            if (!$this->hasPendingHealthProfileInvitation()) {
                $this->healthProfileService
                    ->ensureSelfProfile();
            }

            $this->redirectAfterAuthentication();

        } catch (RuntimeException $exception) {

            $this->view(
                'auth/verification-failed',
                [
                    'title' => 'Email Verification',
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Display the resend verification form.
     */
    public function resendVerification(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        $this->view(
            'auth/resend-verification',
            [
                'title' => 'Resend Verification Email'
            ]
        );
    }

    /**
     * Send a new email verification link.
     */
    public function resendVerificationSubmit(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        try {
            $this->verifyCsrf();

            $email = strtolower(
                trim(
                    (string) $this->request->post(
                        'email',
                        ''
                    )
                )
            );

            /*
            * Always show the same result to the visitor.
            *
            * This prevents revealing whether an email address
            * belongs to a KOBI account.
            */
            $message =
                'If an unverified KOBI account exists for that email ' .
                'address, a new verification link has been sent.';

            if (
                $email === '' ||
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                $this->view(
                    'auth/resend-verification',
                    [
                        'title' => 'Resend Verification Email',
                        'message' => $message
                    ]
                );

                return;
            }

            $user = $this->getUserForVerificationResend($email);

            if ($user !== null) {

                /*
                * Only create a new token when the account is
                * still unverified.
                */
                if ($user['email_verified_at'] === null) {

                    if (
                        $this->userAuthTokenService
                            ->canResendEmailVerification(
                                (int) $user['id']
                            )
                    ) {
                        $this->emailVerificationService->send(
                            (int) $user['id']
                        );
                    }
                }
            }

            $this->view(
                'auth/resend-verification',
                [
                    'title' => 'Resend Verification Email',
                    'message' => $message,
                    'email' => $email
                ]
            );

        } catch (RuntimeException $exception) {

            $this->view(
                'auth/resend-verification',
                [
                    'title' => 'Resend Verification Email',
                    'error' => $exception->getMessage(),
                    'email' => (string) $this->request->post(
                        'email',
                        ''
                    )
                ]
            );
        }
    }

    /**
     * Find a user for verification purposes.
     */
    private function getUserForVerificationResend(
        string $email
    ): ?array {
        /*
        * AuthService intentionally owns authentication behaviour,
        * so expose the lookup through a dedicated service method
        * rather than accessing a repository from the controller.
        */
        return $this->authService->findByEmailForVerification(
            $email
        );
    }

    /**
     * Display the forgot-password form.
     */
    public function forgotPassword(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        $this->view(
            'auth/forgot-password',
            [
                'title' => 'Forgot Password'
            ]
        );
    }

    /**
     * Process a forgot-password request.
     */
    public function sendPasswordReset(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        try {
            $this->verifyCsrf();

            $email = strtolower(
                trim(
                    (string) $this->request->post(
                        'email',
                        ''
                    )
                )
            );

            /*
            * Always display the same response.
            *
            * This prevents revealing whether an email address
            * belongs to a KOBI account.
            */
            $message =
                'If an account exists for that email address, ' .
                'a password reset link has been sent.';

            if (
                $email === '' ||
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                $this->view(
                    'auth/forgot-password',
                    [
                        'title' => 'Forgot Password',
                        'message' => $message
                    ]
                );

                return;
            }

            $user = $this->authService
                ->findByEmailForVerification($email);

            if ($user !== null) {

                /*
                * Only active accounts can receive a password
                * reset link.
                */
                if ($user['status'] === 'active') {

                    $token = $this->userAuthTokenService
                        ->createPasswordResetToken(
                            (int) $user['id']
                        );

                    $resetUrl = url(
                        '/reset-password?token=' .
                        rawurlencode($token)
                    );

                    $this->mailService->send(
                        (string) $user['email'],
                        'Reset your KOBI password',
                        $this->buildPasswordResetEmail(
                            (string) $user['name'],
                            $resetUrl
                        ),
                        $this->buildPasswordResetTextEmail(
                            (string) $user['name'],
                            $resetUrl
                        )
                    );
                }
            }

            $this->view(
                'auth/forgot-password',
                [
                    'title' => 'Forgot Password',
                    'message' => $message,
                    'email' => $email
                ]
            );

        } catch (RuntimeException $exception) {

            $this->view(
                'auth/forgot-password',
                [
                    'title' => 'Forgot Password',
                    'error' => $exception->getMessage(),
                    'email' => (string) $this->request->post(
                        'email',
                        ''
                    )
                ]
            );
        }
    }

    /**
     * Build the HTML password-reset email.
     */
    private function buildPasswordResetEmail(
        string $name,
        string $resetUrl
    ): string {
        $safeName = e($name);
        $safeUrl = e($resetUrl);

        return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset your KOBI password</title>
    </head>
    <body style="margin:0;padding:0;background:#f5f7fa;font-family:Arial,sans-serif;color:#212529;">
        <div style="max-width:600px;margin:40px auto;padding:30px;background:#ffffff;border-radius:8px;">
            <h2 style="margin-top:0;">Reset your KOBI password</h2>

            <p>Hello {$safeName},</p>

            <p>
                We received a request to reset the password for your
                KOBI account.
            </p>

            <p style="margin:30px 0;">
                <a
                    href="{$safeUrl}"
                    style="display:inline-block;padding:12px 20px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px;"
                >
                    Reset Password
                </a>
            </p>

            <p>
                If the button does not work, copy and paste this address
                into your browser:
            </p>

            <p style="word-break:break-all;">
                {$safeUrl}
            </p>

            <p>
                This password-reset link expires after 1 hour.
            </p>

            <p>
                If you did not request a password reset, you can safely
                ignore this email.
            </p>

            <p>
                Regards,<br>
                KOBI Team
            </p>
        </div>
    </body>
    </html>
    HTML;
    }

    /**
     * Build the plain-text password-reset email.
     */
    private function buildPasswordResetTextEmail(
        string $name,
        string $resetUrl
    ): string {
        return
            "Hello {$name},\n\n" .
            "We received a request to reset the password for your " .
            "KOBI account.\n\n" .
            "Use the link below to reset your password:\n\n" .
            "{$resetUrl}\n\n" .
            "This password-reset link expires after 1 hour.\n\n" .
            "If you did not request a password reset, you can safely " .
            "ignore this email.\n\n" .
            "Regards,\n" .
            "KOBI Team";
    }

    /**
     * Display the password-reset form.
     */
    public function resetPassword(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        $token = trim(
            (string) $this->request->get(
                'token',
                ''
            )
        );

        try {
            $this->userAuthTokenService
                ->validatePasswordResetToken($token);

            $this->view(
                'auth/reset-password',
                [
                    'title' => 'Reset Password',
                    'token' => $token
                ]
            );

        } catch (RuntimeException $exception) {

            $this->view(
                'auth/verification-failed',
                [
                    'title' => 'Password Reset',
                    'error' => $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Process a password reset.
     */
    public function resetPasswordSubmit(): void
    {
        if ($this->authService->check()) {
            redirect('/');
        }

        try {
            $this->verifyCsrf();

            $token = trim(
                (string) $this->request->post(
                    'token',
                    ''
                )
            );

            $password = (string) $this->request->post(
                'password',
                ''
            );

            $confirmation = (string) $this->request->post(
                'password_confirmation',
                ''
            );

            /*
            * Consume only after the token has been validated.
            */
            $record = $this->userAuthTokenService
                ->consumePasswordResetToken($token);

            $this->authService->resetPassword(
                (int) $record['user_id'],
                $password,
                $confirmation
            );

            redirect('/login');

        } catch (RuntimeException $exception) {

            $this->view(
                'auth/reset-password',
                [
                    'title' => 'Reset Password',
                    'error' => $exception->getMessage(),
                    'token' => (string) $this->request->post(
                        'token',
                        ''
                    )
                ]
            );
        }
    }

    
}