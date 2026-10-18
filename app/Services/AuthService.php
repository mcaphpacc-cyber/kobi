<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Core\Database;
use App\Repositories\UserRepository;
use RuntimeException;
use Throwable;

class AuthService
{
    private const SESSION_USER_ID = 'auth_user_id';

    public function __construct(
        private UserRepository $userRepository,
        private Session $session,
        private UserAuthTokenService $userAuthTokenService,
        private Database $database
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
     * Create a new KOBI user from a verified Google identity.
     *
     * Google has already verified the email address before
     * this method is called.
     */
    public function createGoogleUser(
        string $name,
        string $email
    ): array {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '') {
            $name = 'KOBI User';
        }

        if (mb_strlen($name) > 100) {
            $name = mb_substr($name, 0, 100);
        }

        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new RuntimeException(
                'Google did not provide a valid email address.'
            );
        }

        if (mb_strlen($email) > 255) {
            throw new RuntimeException(
                'Google email address is too long.'
            );
        }

        /*
        * Never create a second KOBI account for an
        * existing email address.
        */
        if ($this->userRepository->findByEmail($email)) {
            throw new RuntimeException(
                'A KOBI account with this email address already exists. Please sign in to that account first.'
            );
        }

        /*
        * Google-only accounts still require a password_hash
        * because the users.password_hash column is NOT NULL.
        *
        * This random password is intentionally unknown to
        * the user. They can establish a normal password later
        * through the Forgot Password flow.
        */
        $randomPassword = bin2hex(
            random_bytes(32)
        );

        $passwordHash = password_hash(
            $randomPassword,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Unable to secure the Google account.'
            );
        }

        $userId = $this->userRepository->createVerified(
            $name,
            $email,
            $passwordHash
        );

        if ($userId <= 0) {
            throw new RuntimeException(
                'Unable to create the KOBI account.'
            );
        }

        $user = $this->userRepository->findById(
            $userId
        );

        if (!$user) {
            throw new RuntimeException(
                'Unable to retrieve the newly created KOBI account.'
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

        $user = $this->verifyPasswordRecord($user, $password);

        /* Browser-only side effects remain at the existing login boundary. */
        $this->session->regenerate();
        $this->session->put(self::SESSION_USER_ID, (int) $user['id']);
        $this->userRepository->updateLastLogin((int) $user['id']);
        unset($user['password_hash']);
        return $user;
    }

    /** Internal credential verification only: never starts a session or writes a user. */
    public function verifyPasswordRecord(?array $user, string $password, bool $api = false): array
    {
        // Public noncredential dummy bcrypt hash: only equalizes unknown-account work.
        $dummy = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $local = $user !== null && (!$api || ($user['password_set_at'] ?? null) !== null);
        $hash = $local ? $user['password_hash'] : $dummy;
        $verified = ($local || $api) ? password_verify($password, $hash) : false;

        /*
         * Use the same message for unknown users and
         * incorrect passwords.
         */
        if (
            !$local || !$verified
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
        * New and existing accounts must have a verified
        * email address before password authentication succeeds.
        */
        if ($user['email_verified_at'] === null) {
            throw new RuntimeException(
                'Please verify your email address before logging in.'
            );
        }

        return $user;
    }

    /**
     * Mark a user's email address as verified.
     */
    public function verifyEmailAddress(int $userId): void
    {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'The user account could not be found.'
            );
        }

        if ($user['status'] !== 'active') {
            throw new RuntimeException(
                'This account is currently inactive.'
            );
        }

        /*
        * Already verified is not an error.
        *
        * This also makes the operation safe if a valid verification
        * request reaches this method after the account was verified
        * through another request.
        */
        if ($user['email_verified_at'] !== null) {
            return;
        }

        $updated = $this->userRepository->markEmailVerified(
            $userId
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to verify the email address.'
            );
        }
    }

    /**
     * Authenticate an already verified user by ID.
     *
     * Used after successful email verification.
     */
    public function loginById(int $userId): array
    {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'The user account could not be found.'
            );
        }

        if ($user['status'] !== 'active') {
            throw new RuntimeException(
                'This account is currently inactive.'
            );
        }

        if ($user['email_verified_at'] === null) {
            throw new RuntimeException(
                'Please verify your email address before continuing.'
            );
        }

        /*
        * Prevent session fixation after successful authentication.
        */
        $this->session->regenerate();

        $this->session->put(
            self::SESSION_USER_ID,
            $userId
        );

        $this->userRepository->updateLastLogin(
            $userId
        );

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
     * Update the currently authenticated user's profile.
     *
     * Changing the email address resets email verification.
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
    public function updateProfile(
        string $name,
        string $email
    ): array {
        $userId = $this->id();

        if (!$userId) {
            throw new RuntimeException(
                'You must be logged in to update your profile.'
            );
        }

        $name = trim($name);
        $email = strtolower(trim($email));

        $this->validateProfile(
            $name,
            $email
        );

        $currentUser = $this->userRepository->findById(
            $userId
        );

        if (!$currentUser) {
            throw new RuntimeException(
                'Unable to retrieve your current profile.'
            );
        }

        $emailChanged =
            strtolower((string) $currentUser['email']) !== $email;

        $existingUser = $this->userRepository
            ->findByEmail($email);

        if (
            $existingUser &&
            (int) $existingUser['id'] !== $userId
        ) {
            throw new RuntimeException(
                'An account with this email address already exists.'
            );
        }

        $updated = $this->userRepository->updateProfile(
            $userId,
            $name,
            $email,
            $emailChanged
        );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to update your profile.'
            );
        }

        $user = $this->userRepository->findById(
            $userId
        );

        if (!$user) {
            throw new RuntimeException(
                'Unable to retrieve your updated profile.'
            );
        }

        return $user;
    }

    /** Private account contact data only; this does not establish a phone identity. */
    public function updateMobileNumber(mixed $input): void
    {
        $userId = $this->id();
        if ($userId === null) {
            throw new RuntimeException('You must be logged in to update your profile.');
        }
        $user = $this->userRepository->findById($userId);
        if (!$user || $user['status'] !== 'active') {
            throw new RuntimeException('Unable to update your mobile number. Please try again.');
        }
        if (!is_string($input) || strlen($input) > 64) {
            throw new RuntimeException('Enter a mobile number of at most 64 characters.');
        }
        $value = trim($input);
        $mobile = null;
        if ($value !== '') {
            if (!preg_match('/\A[+0-9 ()-]+\z/', $value)) {
                throw new RuntimeException('Use digits and an international + prefix. Spaces, hyphens and parentheses are allowed.');
            }
            $mobile = str_replace([' ', '-', '(', ')'], '', $value);
            if (!preg_match('/\A\+[1-9][0-9]{1,14}\z/', $mobile)) {
                throw new RuntimeException('Enter an international number beginning with + and containing 2 to 15 digits.');
            }
        }
        $this->userRepository->updateMobileNumber($userId, $mobile);
        $saved = $this->userRepository->findById($userId);
        if (!$saved || $saved['status'] !== 'active' || ($saved['mobile_number'] ?? null) !== $mobile) {
            throw new RuntimeException('Unable to update your mobile number. Please try again.');
        }
    }

    /**
     * Change the current user's password.
     */
    public function changePassword(
        string $currentPassword,
        string $newPassword,
        string $confirmation
    ): void {
        $userId = $this->id();

        if (!$userId) {
            throw new RuntimeException(
                'You must be logged in to change your password.'
            );
        }

        if ($currentPassword === '') {
            throw new RuntimeException(
                'Current password is required.'
            );
        }

        $this->validateNewPassword($newPassword, $confirmation);

        $user = $this->userRepository->findByEmail(
            $this->userRepository->findById($userId)['email']
        );

        if (
            !$user ||
            !password_verify(
                $currentPassword,
                $user['password_hash']
            )
        ) {
            throw new RuntimeException(
                'Current password is incorrect.'
            );
        }

        $passwordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Unable to secure the new password.'
            );
        }

        $this->persistPassword(
            $userId,
            $passwordHash,
            'Unable to update your password.'
        );

        /*
        * Regenerate the session after a successful
        * password change.
        */
        $this->session->regenerate();

        $this->session->put(
            self::SESSION_USER_ID,
            $userId
        );
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

    /**
     * Validate profile data.
     */
    private function validateProfile(
        string $name,
        string $email
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
    }

    /**
     * Find a user by email for email-verification operations.
     */
    public function findByEmailForVerification(
        string $email
    ): ?array {
        $email = strtolower(trim($email));

        if (
            $email === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return null;
        }

        return $this->userRepository->findByEmail(
            $email
        );
    }

    /**
     * Reset a user's password using a password-reset token.
     */
    public function resetPassword(
        int $userId,
        string $newPassword,
        string $confirmation
    ): void {
        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user account.'
            );
        }

        $this->validateNewPassword($newPassword, $confirmation);

        $user = $this->userRepository->findById(
            $userId
        );

        if (!$user) {
            throw new RuntimeException(
                'The user account could not be found.'
            );
        }

        if ($user['status'] !== 'active') {
            throw new RuntimeException(
                'This account is currently inactive.'
            );
        }

        $passwordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Unable to secure the new password.'
            );
        }

        $this->persistPassword(
            $userId,
            $passwordHash,
            'Unable to reset your password.'
        );

        /*
        * A password reset should never automatically authenticate
        * the user. They must explicitly log in with the new password.
        */
        $this->session->remove(
            self::SESSION_USER_ID
        );
    }

    /**
     * Commit the password and required token invalidation together on the shared connection.
     * Session changes happen only after this operation commits.
     */
    private function persistPassword(int $userId, string $passwordHash, string $failureMessage): void
    {
        $connection = $this->database->connection();

        if (!$connection->beginTransaction()) {
            throw new RuntimeException($failureMessage);
        }

        try {
            if (!$this->userRepository->updatePassword($userId, $passwordHash)) {
                throw new RuntimeException($failureMessage);
            }

            $this->userAuthTokenService->invalidateUnusedPasswordResetTokens($userId);

            if (!$connection->commit()) {
                throw new RuntimeException($failureMessage);
            }
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Validate password data before consuming a reset token or updating a password.
     */
    public function validateNewPassword(string $password, string $confirmation): void
    {
        if (strlen($password) < 8) {
            throw new RuntimeException(
                'New password must be at least 8 characters long.'
            );
        }

        if ($password !== $confirmation) {
            throw new RuntimeException('New passwords do not match.');
        }
    }
}
