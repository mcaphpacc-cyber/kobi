<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;
use RuntimeException;
use App\Services\HealthProfileService;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private HealthProfileService $healthProfileService,
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

            /*
            * Automatically authenticate the newly
            * registered user.
            */
            $this->authService->login(
                $user['email'],
                (string) $this->request->post('password', '')
            );

            /*
            * Normal registrations receive a self health
            * profile automatically.
            *
            * Invitation-based registrations will receive
            * access to the invited family profile instead.
            */
            if (!$this->hasPendingHealthProfileInvitation())
            {
                $this->healthProfileService
                    ->ensureSelfProfile();
            }

            $this->redirectAfterAuthentication();

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

            $this->view(
                'auth/login',
                [
                    'title' => 'Login',
                    'error' => $exception->getMessage(),
                    'old' => [
                        'email' => (string) $this->request->post('email', '')
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
}