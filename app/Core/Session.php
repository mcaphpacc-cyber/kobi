<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    /**
     * Start the session if it is not already active.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Store a session value.
     */
    public function put(
        string $key,
        mixed $value
    ): void {
        $this->start();

        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a session value.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Determine whether a session value exists.
     */
    public function has(string $key): bool
    {
        $this->start();

        return array_key_exists(
            $key,
            $_SESSION
        );
    }

    /**
     * Remove a session value.
     */
    public function remove(string $key): void
    {
        $this->start();

        unset($_SESSION[$key]);
    }

    /**
     * Regenerate the session ID.
     */
    public function regenerate(): void
    {
        $this->start();

        session_regenerate_id(true);
    }

    /**
     * Destroy the current session.
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            session_destroy();
        }
    }

    /**
     * Get the current CSRF token.
     */
    public function csrfToken(): string
    {
        $this->start();

        if (
            !isset($_SESSION['_csrf_token']) ||
            !is_string($_SESSION['_csrf_token']) ||
            $_SESSION['_csrf_token'] === ''
        ) {
            $_SESSION['_csrf_token'] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Validate a submitted CSRF token.
     */
    public function verifyCsrfToken(
        ?string $token
    ): bool {
        if (
            $token === null ||
            $token === ''
        ) {
            return false;
        }

        $sessionToken = $this->get('_csrf_token');

        if (
            !is_string($sessionToken) ||
            $sessionToken === ''
        ) {
            return false;
        }

        return hash_equals(
            $sessionToken,
            $token
        );
    }
}
