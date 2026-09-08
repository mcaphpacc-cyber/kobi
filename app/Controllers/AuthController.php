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
            * Ensure the newly registered user has
            * a self health profile.
            */
            $this->healthProfileService
                ->ensureSelfProfile();

            redirect('/');

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

            redirect('/');

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
}