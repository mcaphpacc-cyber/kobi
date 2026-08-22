<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;
use RuntimeException;

class AuthService
{
    private const SESSION_USER_ID = 'auth_user_id';

    public function __construct(
        private UserRepository $userRepository,
        private Session $session
    ) {
    }

    /**
     * Register a new user.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     status: string,
     *     email_verified_at: ?string,
     *     last_login_at: ?string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function register(
        string $name,
        string $email,
        string $password
    ): array {
        $name = trim($name);
        $email = strtolower(trim($email));

        $this->validateRegistration(
            $name,
            $email,
            $password
        );

        if ($this->userRepository->findByEmail($email)) {
            throw new RuntimeException(
                'An account with this email address already exists.'
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Unable to secure the password.'
            );
        }

        $userId = $this->userRepository->create(
            $name,
            $email,
            $passwordHash
        );

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'Unable to retrieve the newly created account.'
            );
        }

        return $user;
    }

    /**
     * Authenticate a user.
     */
    public function login(
        string $email,
        string $password
    ): array {
        $email = strtolower(trim($email));

        if (
            $email === '' ||
            $password === ''
        ) {
            throw new RuntimeException(
                'Invalid email address or password.'
            );
        }

        $user = $this->userRepository
            ->findByEmail($email);

        /*
         * Use the same message for unknown users and
         * incorrect passwords.
         */
        if (
            !$user ||
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            throw new RuntimeException(
                'Invalid email address or password.'
            );
        }

        if ($user['status'] !== 'active') {
            throw new RuntimeException(
                'This account is currently inactive.'
            );
        }

        /*
         * Prevent session fixation after successful login.
         */
        $this->session->regenerate();

        $this->session->put(
            self::SESSION_USER_ID,
            (int) $user['id']
        );

        $this->userRepository->updateLastLogin(
            (int) $user['id']
        );

        /*
         * Return the authenticated user without
         * exposing the password hash.
         */
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Determine whether a user is authenticated.
     */
    public function check(): bool
    {
        return $this->session->has(
            self::SESSION_USER_ID
        );
    }

    /**
     * Return the currently authenticated user.
     */
    public function user(): ?array
    {
        $userId = $this->session->get(
            self::SESSION_USER_ID
        );

        if (!$userId) {
            return null;
        }

        $user = $this->userRepository->findById(
            (int) $userId
        );

        if (!$user) {
            $this->logout();

            return null;
        }

        if ($user['status'] !== 'active') {
            $this->logout();

            return null;
        }

        return $user;
    }

    /**
     * Return the current user's ID.
     */
    public function id(): ?int
    {
        $userId = $this->session->get(
            self::SESSION_USER_ID
        );

        if (!$userId) {
            return null;
        }

        return (int) $userId;
    }

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Validate registration data.
     */
    private function validateRegistration(
        string $name,
        string $email,
        string $password
    ): void {
        if ($name === '') {
            throw new RuntimeException(
                'Name is required.'
            );
        }

        if (mb_strlen($name) > 100) {
            throw new RuntimeException(
                'Name must not exceed 100 characters.'
            );
        }

        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new RuntimeException(
                'Please provide a valid email address.'
            );
        }

        if (mb_strlen($email) > 255) {
            throw new RuntimeException(
                'Email address is too long.'
            );
        }

        if (strlen($password) < 8) {
            throw new RuntimeException(
                'Password must be at least 8 characters long.'
            );
        }
    }
}