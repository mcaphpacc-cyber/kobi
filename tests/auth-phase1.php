<?php

declare(strict_types=1);

// Database-free regression checks. Run: php tests/auth-phase1.php
namespace App\Core {
    // Keep OAuth diagnostics in memory during tests; never write application logs.
    final class Logger
    {
        public static array $messages = [];
        public static function error(string $message): void { self::$messages[] = $message; }
    }
}

namespace {
    $redirectCheck = null;
    $abortCheck = null;
    $googleTestEnvironment = [];
    function env(string $key, mixed $default = null): mixed
    {
        global $googleTestEnvironment;
        return $googleTestEnvironment[$key] ?? $default;
    }
    function abort(int $statusCode = 404, string $message = ''): never
    {
        global $abortCheck;
        check(is_callable($abortCheck), 'Unexpected test abort');
        try { $abortCheck($statusCode, $message); }
        catch (\Throwable $error) {
            fwrite(STDERR, 'Abort verification failed: ' . get_class($error) . ': ' . $error->getMessage() . "\n");
            exit(1);
        }
        echo "PASS\n";
        exit(0);
    }
    function redirect(string $path): never
    {
        global $redirectCheck;
        // Test failures must terminate this child process, never reach controller catches.
        check(is_callable($redirectCheck), 'Unexpected test redirect');
        try {
            $redirectCheck($path);
        } catch (\Throwable $error) {
            fwrite(STDERR, 'Redirect verification failed: ' . get_class($error) . ': ' . $error->getMessage() . "\n");
            exit(1);
        }
        echo "PASS\n";
        exit(0);
    }

    // Load production classes without bootstrap, credentials, sessions or real mail.
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($path)) { require $path; }
        }
    });
    function e(?string $value): string
    { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function url(string $path = ''): string
    { return 'https://kobi.example.invalid/' . ltrim($path, '/'); }
    function csrfField(): string
    { return '<input type="hidden" name="_csrf_token" value="test-csrf">'; }

    set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    use App\Controllers\AccountController;
    use App\Controllers\AuthController;
    use App\Controllers\GoogleAuthController;
    use App\Core\Request;
    use App\Core\Session;
    use App\Core\Database;
    use App\Core\Container;
    use App\Repositories\UserRepository;
    use App\Repositories\UserAuthTokenRepository;
    use App\Repositories\UserAuthIdentityRepository;
    use App\Services\AuthService;
    use App\Services\EmailVerificationService;
    use App\Services\GoogleAuthService;
    use App\Services\GoogleOAuthStateService;
    use App\Services\HealthProfileService;
    use App\Services\MailService;
    use App\Services\UserAuthIdentityService;
    use App\Services\UserAuthTokenService;

    function check(bool $condition, string $label): void
    {
        if (!$condition) {
            fwrite(STDERR, 'Assertion failed: ' . $label . "\n");
            exit(1);
        }
    }

    function fails(callable $action, string $message): void
    {
        try { $action(); } catch (\RuntimeException $error) {
            check($error->getMessage() === $message, 'Expected validation message');
            return;
        }
        throw new \LogicException('Expected operation to fail');
    }

    class MemorySession extends Session
    {
        public array $values = [];
        public int $regenerations = 0;
        public function start(): void {}
        public function put(string $key, mixed $value): void { $this->values[$key] = $value; }
        public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
        public function has(string $key): bool { return array_key_exists($key, $this->values); }
        public function remove(string $key): void { unset($this->values[$key]); }
        public function regenerate(): void { ++$this->regenerations; }
        public function destroy(): void { $this->values = []; }
    }

    class MemoryUsers extends UserRepository
    {
        public array $rows = [];
        public int $updates = 0;
        public ?\Throwable $passwordFailure = null;
        public ?\Throwable $profileFailure = null;
        public ?\Throwable $lookupFailure = null;
        public function __construct() {}
        public function findById(int $id): ?array { return $this->rows[$id] ?? null; }
        public function findByEmail(string $email): ?array
        {
            if ($this->lookupFailure) { throw $this->lookupFailure; }
            foreach ($this->rows as $row) { if ($row['email'] === strtolower(trim($email))) { return $row; } }
            return null;
        }
        public function updatePassword(int $userId, string $passwordHash): bool
        {
            if ($this->passwordFailure) { throw $this->passwordFailure; }
            ++$this->updates;
            $this->rows[$userId]['password_hash'] = $passwordHash;
            $this->rows[$userId]['password_set_at'] = date('Y-m-d H:i:s');
            return true;
        }
        public function hasPassword(int $userId): bool { return !empty($this->rows[$userId]['password_set_at']); }
        public function updateProfile(int $userId, string $name, string $email, bool $emailChanged = false): bool
        {
            if ($this->profileFailure) { throw $this->profileFailure; }
            $this->rows[$userId]['name'] = $name;
            $this->rows[$userId]['email'] = $email;
            if ($emailChanged) { $this->rows[$userId]['email_verified_at'] = null; }
            return true;
        }
        public function markEmailVerified(int $userId): bool
        {
            $this->rows[$userId]['email_verified_at'] = date('Y-m-d H:i:s');
            return true;
        }
        public function updateLastLogin(int $userId): bool { return true; }
        public function createVerified(string $name, string $email, string $passwordHash): int
        {
            $id = count($this->rows) + 1;
            $this->rows[$id] = [
                'id' => $id, 'name' => $name, 'email' => $email, 'password_hash' => $passwordHash,
                'password_set_at' => null, 'email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active'
            ];
            return $id;
        }
    }

    class MemoryTokens extends UserAuthTokenRepository
    {
        public array $rows = [];
        public bool $recent = false;
        public ?\Throwable $invalidationFailure = null;
        public ?\Throwable $lookupFailure = null;
        public function __construct() {}
        public function create(int $userId, string $tokenHash, string $type, string $expiresAt): int
        {
            $id = count($this->rows) + 1;
            $this->rows[$id] = ['id' => $id, 'user_id' => $userId, 'token_hash' => $tokenHash,
                'type' => $type, 'expires_at' => $expiresAt, 'used_at' => null];
            return $id;
        }
        public function findValidToken(string $tokenHash, string $type): ?array
        {
            if ($this->lookupFailure) { throw $this->lookupFailure; }
            foreach ($this->rows as $row) {
                if ($row['token_hash'] === $tokenHash && $row['type'] === $type &&
                    $row['used_at'] === null && strtotime($row['expires_at']) > time()) { return $row; }
            }
            return null;
        }
        public function markUsed(int $tokenId): bool
        {
            if ($this->rows[$tokenId]['used_at'] !== null || strtotime($this->rows[$tokenId]['expires_at']) <= time()) { return false; }
            $this->rows[$tokenId]['used_at'] = date('Y-m-d H:i:s');
            return true;
        }
        public function invalidateUnusedTokens(int $userId, string $type): bool
        {
            $changed = false;
            foreach ($this->rows as &$row) {
                if ($row['user_id'] === $userId && $row['type'] === $type && $row['used_at'] === null) {
                    $row['used_at'] = date('Y-m-d H:i:s');
                    $changed = true;
                }
            }
            if ($this->invalidationFailure && $type === 'password_reset') { throw $this->invalidationFailure; }
            return $changed;
        }
        public function hasRecentToken(int $userId, string $type, int $seconds = 60): bool { return $this->recent; }
    }

    class MemoryIdentities extends UserAuthIdentityRepository
    {
        public array $rows = [];
        public ?\Throwable $deleteFailure = null;
        public ?\Throwable $findFailure = null;
        public function __construct() {}
        public function findByUserId(int $userId): array
        {
            if ($this->findFailure) { throw $this->findFailure; }
            return array_values(array_filter($this->rows, fn ($row) => $row['user_id'] === $userId));
        }
        public function findByProviderUserId(string $provider, string $providerUserId): ?array
        {
            foreach ($this->rows as $row) {
                if ($row['provider'] === $provider && $row['provider_user_id'] === $providerUserId) { return $row; }
            }
            return null;
        }
        public function create(int $userId, string $provider, string $providerUserId, ?string $providerEmail = null): int
        {
            $id = count($this->rows) + 1;
            $this->rows[$id] = ['id' => $id, 'user_id' => $userId, 'provider' => $provider,
                'provider_user_id' => $providerUserId, 'provider_email' => $providerEmail];
            return $id;
        }
        public function delete(int $identityId): bool
        {
            if ($this->deleteFailure) { throw $this->deleteFailure; }
            unset($this->rows[$identityId]); return true;
        }
    }

    // Exercise production transaction orchestration with snapshots; this does not prove PDO/MySQL rollback.
    class MemoryTransaction extends \PDO
    {
        public int $begins = 0;
        public int $commits = 0;
        public int $rollbacks = 0;
        public bool $commitFailure = false;
        private ?array $snapshot = null;
        public function __construct(private MemoryUsers $users, private MemoryTokens $tokens) {}
        public function beginTransaction(): bool
        {
            check($this->snapshot === null, 'No nested test transaction');
            ++$this->begins;
            $this->snapshot = [$this->users->rows, $this->tokens->rows];
            return true;
        }
        public function commit(): bool
        {
            check($this->snapshot !== null, 'Commit has active transaction');
            if ($this->commitFailure) { throw new \PDOException('PRIVATE_TEST_SQL_DETAIL commit'); }
            ++$this->commits; $this->snapshot = null; return true;
        }
        public function rollBack(): bool
        {
            check($this->snapshot !== null, 'Rollback has active transaction');
            [$this->users->rows, $this->tokens->rows] = $this->snapshot;
            ++$this->rollbacks; $this->snapshot = null; return true;
        }
        public function inTransaction(): bool { return $this->snapshot !== null; }
    }
    class MemoryDatabase extends Database
    {
        public function __construct(private \PDO $testConnection) {}
        public function connection(): \PDO { return $this->testConnection; }
    }

    class FakeGoogle extends GoogleAuthService
    {
        public ?\Throwable $failure = null;
        public int $calls = 0;
        public int $authorizationCalls = 0;
        public int $exceptions = 0;
        public function __construct() {}
        public function authenticate(string $code): array
        {
            ++$this->calls;
            if ($this->failure) { ++$this->exceptions; throw $this->failure; }
            return ['provider_user_id' => 'subject', 'name' => 'Test User',
                'email' => 'test@example.invalid', 'email_verified' => true, 'picture' => null];
        }
        public function getAuthorizationUrl(string $state): string
        {
            ++$this->authorizationCalls;
            ++$this->exceptions;
            throw new \LogicException('Internal provider failure');
        }
    }
    class FakeProfiles extends HealthProfileService
    {
        public int $created = 0;
        public function __construct() {}
        public function ensureSelfProfile(): int { ++$this->created; return 1; }
    }
    class FakeMail extends MailService
    {
        public array $messages = [];
        public ?\Throwable $failure = null;
        public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): void
        {
            if ($this->failure) { throw $this->failure; }
            $this->messages[] = compact('to', 'subject', 'htmlBody', 'textBody');
        }
    }
    trait CapturesView
    {
        public array $rendered = [];
        protected function view(string $view, array $data = []): void { $this->rendered = [$view, $data]; }
    }
    class TestAuthController extends AuthController { use CapturesView; }
    class TestAccountController extends AccountController { use CapturesView; }
    class TestGoogleController extends GoogleAuthController { use CapturesView; }

    function controller(string $class, array $dependencies): object
    {
        $reflection = new \ReflectionClass($class);
        $arguments = [];
        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();
            $arguments[] = $dependencies[$type] ?? (new \ReflectionClass($type))->newInstanceWithoutConstructor();
        }
        return $reflection->newInstanceArgs($arguments);
    }

    function renderAccount(array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require dirname(__DIR__) . '/app/Views/account/index.php';
            $html = ob_get_contents();
        } finally { ob_end_clean(); }
        check(str_contains($html, 'Connected'), 'Connected account rendered');
        check(str_contains($html, 'Disconnect') === $data['hasPassword'], 'Disconnect control rendered correctly');
        return $html;
    }

    $scenarios = ['reset-short', 'reset-mismatch', 'reset-success', 'reset-expired', 'reset-csrf',
        'change-wrong', 'change-mismatch', 'change-success', 'change-no-tokens', 'email-change',
        'oauth-cancel-login', 'oauth-cancel-link', 'oauth-invalid-link', 'oauth-missing-state',
        'oauth-internal-login', 'oauth-internal-link', 'oauth-link-initiation', 'oauth-existing',
        'oauth-new', 'oauth-collision', 'oauth-link-success', 'unlink-password', 'unlink-only',
        'unlink-after-reset', 'unlink-other-user', 'account-profile-error', 'account-password-error',
        'account-unlink-error', 'account-google-error', 'account-profile-anonymous',
        'f1-reset-invalidation-failure', 'f1-change-invalidation-failure',
        'f1-reset-update-failure', 'f1-change-commit-failure',
        'f2-reset-get-pdo', 'f2-profile-pdo', 'f2-password-pdo', 'f2-unlink-pdo',
        'f2-profile-unexpected', 'f2-account-render-failure',
        'f3-config-login', 'f3-config-link', 'f3-config-callback-login', 'f3-config-callback-link',
        'f3-dependency-login', 'f3-dependency-link', 'task15-auth-errors'];

    if (!isset($argv[1])) {
        foreach ($scenarios as $scenario) {
            $process = proc_open([PHP_BINARY, __FILE__, $scenario], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $status = proc_close($process);
            if ($status !== 0 || trim($output) !== 'PASS') {
                fwrite(STDERR, "FAIL $scenario: $error\n"); exit(1);
            }
            echo "PASS $scenario\n";
        }
        echo count($scenarios) . " database-free scenarios passed.\n";
        exit(0);
    }

    try {
        $scenario = $argv[1];
        check(in_array($scenario, $scenarios, true), 'Unknown scenario');
        $session = new MemorySession();
        $users = new MemoryUsers();
        $users->rows[1] = ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.invalid',
            'password_hash' => password_hash('original-test-password', PASSWORD_DEFAULT),
            'password_set_at' => '2026-01-01 00:00:00', 'email_verified_at' => '2026-01-01 00:00:00', 'status' => 'active'];
        $repository = new MemoryTokens();
        $tokens = new UserAuthTokenService($repository);
        $transaction = new MemoryTransaction($users, $repository);
        $database = new MemoryDatabase($transaction);
        $auth = new AuthService($users, $session, $tokens, $database);
        $identities = new MemoryIdentities();
        $identities->create(1, 'google', 'subject', 'test@example.invalid');
        $identityService = new UserAuthIdentityService($identities, $users);
        $mail = new FakeMail();
        $email = new EmailVerificationService($users, $tokens, $mail);
        $profiles = new FakeProfiles();
        $google = new FakeGoogle();
        $state = new GoogleOAuthStateService($session);
        $dependencies = [AuthService::class => $auth, Session::class => $session,
            UserAuthTokenService::class => $tokens, UserAuthIdentityService::class => $identityService,
            EmailVerificationService::class => $email, MailService::class => $mail,
            Request::class => new Request(), HealthProfileService::class => $profiles,
            GoogleAuthService::class => $google, GoogleOAuthStateService::class => $state];
        $raw = str_repeat('a', 64);
        $repository->create(1, hash('sha256', $raw), 'password_reset', date('Y-m-d H:i:s', time() + 3600));
        $repository->create(1, hash('sha256', str_repeat('b', 64)), 'password_reset', date('Y-m-d H:i:s', time() + 3600));
        $repository->create(1, hash('sha256', str_repeat('c', 64)), 'email_verification', date('Y-m-d H:i:s', time() + 86400));
        $repository->create(2, hash('sha256', str_repeat('d', 64)), 'password_reset', date('Y-m-d H:i:s', time() + 3600));
        $session->put('_csrf_token', 'test-csrf');
        $_POST = ['_csrf_token' => 'test-csrf'];
        $_GET = [];

        $passwordUpdated = function () use ($users, $repository, $transaction): void {
            check(password_verify('replacement-test-password', $users->rows[1]['password_hash']), 'Password updated');
            check($users->hasPassword(1), 'Password marker set');
            check($repository->rows[1]['used_at'] !== null && $repository->rows[2]['used_at'] !== null, 'Reset tokens invalidated');
            check($repository->rows[3]['used_at'] === null && $repository->rows[4]['used_at'] === null, 'Other type/user tokens preserved');
            check($transaction->begins === 1 && $transaction->commits === 1 && $transaction->rollbacks === 0 && !$transaction->inTransaction(), 'Password and token updates commit before success');
        };

        if ($scenario === 'task15-auth-errors') {
            $ctrl = controller(TestAuthController::class, $dependencies);
            $defaults = ['_csrf_token' => 'test-csrf', 'name' => 'Test User',
                'email' => 'test@example.invalid', 'password' => 'original-test-password'];
            $registrationCases = [
                [['name' => ''], 'Name is required.'],
                [['name' => str_repeat('a', 101)], 'Name must not exceed 100 characters.'],
                [['email' => 'invalid'], 'Please provide a valid email address.'],
                [['password' => 'short'], 'Password must be at least 8 characters long.'],
                [[], 'An account with this email address already exists.']
            ];
            foreach ($registrationCases as [$input, $expected]) {
                $_POST = array_replace($defaults, $input);
                $ctrl->store();
                check($ctrl->rendered[1]['error'] === $expected, 'Registration validation wording unchanged');
                check(!isset($ctrl->rendered[1]['old']['password']), 'Registration never retains password');
            }
            foreach ([['password' => 'wrong'], ['email' => 'unknown@example.invalid']] as $input) {
                $_POST = array_replace($defaults, $input);
                $ctrl->authenticate();
                check($ctrl->rendered[1]['error'] === 'Invalid email address or password.', 'Login rejection unchanged');
            }
            $_POST = $defaults;
            $users->rows[1]['email_verified_at'] = null;
            $ctrl->authenticate();
            check($ctrl->rendered[1]['error'] === 'Please verify your email address before logging in.' &&
                $ctrl->rendered[1]['verification_required'], 'Unverified account wording/action unchanged');
            $users->rows[1]['email_verified_at'] = '2026-01-01 00:00:00';
            $users->rows[1]['status'] = 'inactive';
            $ctrl->authenticate();
            check($ctrl->rendered[1]['error'] === 'This account is currently inactive.', 'Inactive account unchanged');
            $users->rows[1]['status'] = 'active';
            $message = 'If an account exists for that email address, a password reset link has been sent.';
            foreach (['invalid', 'unknown@example.invalid', 'test@example.invalid'] as $address) {
                $_POST = array_replace($defaults, ['email' => $address]);
                $ctrl->sendPasswordReset();
                check($ctrl->rendered[1]['message'] === $message, 'Reset request response unchanged');
            }
            check(count($mail->messages) === 1, 'Only known active account receives synthetic mail');
            $handlers = ['store' => ['auth/register', 'Unable to create your account. Please try again.'],
                'authenticate' => ['auth/login', 'Unable to log in. Please try again.'],
                'sendPasswordReset' => ['auth/forgot-password', 'Unable to request a password reset. Please try again.']];
            $private = 'PDOException SQLSTATE users user_auth_tokens D:/private/config.php #0 App\\Services\\AuthService::login';
            foreach ($handlers as $method => [$view, $fallback]) {
                $_POST = array_replace($defaults, ['_csrf_token' => 'invalid']);
                $ctrl->$method();
                check($ctrl->rendered[1]['error'] === 'Invalid security token. Please try again.', 'CSRF wording unchanged');
                $_POST = $defaults;
                foreach ([new \PDOException($private), new \RuntimeException($private),
                    new \LogicException($private), new \Error($private),
                    new \PDOException('Invalid security token. Please try again.')] as $failure) {
                    $users->lookupFailure = $failure;
                    $ctrl->$method();
                    check($ctrl->rendered[0] === $view && $ctrl->rendered[1]['error'] === $fallback, 'Unexpected throwable uses safe fallback');
                    check(!str_contains(json_encode($ctrl->rendered), $private), 'Private payload absent from view data');
                    $render = static function (string $view, array $data): string {
                        extract($data, EXTR_SKIP);
                        ob_start();
                        try { require dirname(__DIR__) . '/app/Views/' . $view . '.php'; return ob_get_contents(); }
                        finally { ob_end_clean(); }
                    };
                    $html = $render($view, $ctrl->rendered[1]);
                    check(str_contains($html, $fallback), 'Generic error visibly rendered');
                    foreach (['PDOException', 'SQLSTATE', 'user_auth_tokens', 'D:/private', '#0', 'App\\Services'] as $detail) {
                        check(!str_contains($html, $detail), 'Internal string absent from rendered output');
                    }
                    if ($method === 'authenticate') { check(!$ctrl->rendered[1]['verification_required'], 'Internal failure does not suggest verification'); }
                }
                $users->lookupFailure = null;
            }
            $mail->failure = new \RuntimeException($private);
            $_POST = $defaults;
            $ctrl->sendPasswordReset();
            check($ctrl->rendered[1]['error'] === $handlers['sendPasswordReset'][1], 'Internal mail failure hidden');
            check(!str_contains(implode(' ', \App\Core\Logger::$messages), $private), 'Logs exclude raw exception payload');
            check(!$auth->check() && $session->regenerations === 0, 'Failed login does not authenticate');
        } elseif (str_starts_with($scenario, 'f1-')) {
            $isReset = str_starts_with($scenario, 'f1-reset-');
            $session->put('auth_user_id', 1);
            if ($isReset) { $session->remove('auth_user_id'); }
            $originalUsers = $users->rows;
            $originalTokens = $repository->rows;
            if (str_contains($scenario, 'invalidation')) {
                $repository->invalidationFailure = new \PDOException('PRIVATE_TEST_SQL_DETAIL invalidation');
            } elseif (str_contains($scenario, 'update')) {
                $users->passwordFailure = new \PDOException('PRIVATE_TEST_SQL_DETAIL update');
            } else { $transaction->commitFailure = true; }
            if ($isReset) {
                $_POST += ['token' => $raw, 'password' => 'replacement-test-password', 'password_confirmation' => 'replacement-test-password'];
                $ctrl = controller(TestAuthController::class, $dependencies);
                $ctrl->resetPasswordSubmit();
                check($ctrl->rendered[1]['error'] === 'Unable to reset your password. Please try again.', 'Reset failure safely rendered');
                check(!$auth->check(), 'Failed reset does not authenticate');
                check($repository->rows[1]['used_at'] !== null, 'Consumption remains outside transaction');
                $originalTokens[1]['used_at'] = $repository->rows[1]['used_at'];
                $tokens->validatePasswordResetToken(str_repeat('b', 64));
            } else {
                $_POST += ['current_password' => 'original-test-password', 'new_password' => 'replacement-test-password', 'new_password_confirmation' => 'replacement-test-password'];
                $ctrl = controller(TestAccountController::class, $dependencies);
                $ctrl->changePassword();
                check($ctrl->rendered[1]['passwordError'] === 'Unable to update your password. Please try again.', 'Change failure safely rendered');
                check($auth->id() === 1, 'Failed change retains original authentication');
            }
            check($users->rows === $originalUsers && $repository->rows === $originalTokens, 'Failed password operation rolls back all transactional writes');
            check($transaction->begins === 1 && $transaction->commits === 0 && $transaction->rollbacks === 1 && !$transaction->inTransaction(), 'Production code requests rollback without commit');
            check($session->regenerations === 0, 'Failed transaction skips success session handling');
            check(!str_contains(json_encode($ctrl->rendered), 'PRIVATE_TEST_SQL_DETAIL'), 'Database detail absent from view');
            check(!str_contains(implode(' ', \App\Core\Logger::$messages), 'PRIVATE_TEST_SQL_DETAIL'), 'Diagnostic logging excludes raw failure payload');
        } elseif (str_starts_with($scenario, 'f2-')) {
            $failure = new \PDOException('PRIVATE_TEST_SQL_DETAIL database/path/token');
            if ($scenario === 'f2-reset-get-pdo') {
                $repository->lookupFailure = $failure;
                $_GET['token'] = $raw;
                $ctrl = controller(TestAuthController::class, $dependencies);
                $ctrl->resetPassword();
                check($ctrl->rendered[1]['error'] === 'Unable to reset your password. Please try again.', 'Reset GET hides PDO error');
            } else {
                $session->put('auth_user_id', 1);
                $ctrl = controller(TestAccountController::class, $dependencies);
                if ($scenario === 'f2-account-render-failure') {
                    $identities->findFailure = $failure;
                    $abortCheck = function ($status, $message) use ($auth): void {
                        check($status === 500 && $message === 'Unable to load your account. Please try again.', 'Secondary repository failure safely aborts');
                        check($auth->id() === 1, 'Secondary failure preserves authentication');
                        check(!str_contains(implode(' ', \App\Core\Logger::$messages), 'PRIVATE_TEST_SQL_DETAIL'), 'Secondary failure log is sanitized');
                    };
                    $_POST += ['current_password' => 'wrong', 'new_password' => 'replacement-test-password', 'new_password_confirmation' => 'replacement-test-password'];
                    $ctrl->changePassword();
                    check(false, 'Secondary render failure must abort');
                } elseif ($scenario === 'f2-password-pdo') {
                    $users->passwordFailure = $failure;
                    $_POST += ['current_password' => 'original-test-password', 'new_password' => 'replacement-test-password', 'new_password_confirmation' => 'replacement-test-password'];
                    $ctrl->changePassword();
                    check($ctrl->rendered[1]['passwordError'] === 'Unable to update your password. Please try again.', 'Password handler hides PDO error');
                } elseif ($scenario === 'f2-unlink-pdo') {
                    $identities->deleteFailure = $failure; $_POST['identity_id'] = 1;
                    $ctrl->unlinkAuthentication();
                    check($ctrl->rendered[1]['error'] === 'Unable to disconnect Google. Please try again.', 'Unlink handler hides PDO error');
                    check(count($identities->rows) === 1, 'Failed unlink retains identity');
                } else {
                    $users->profileFailure = $scenario === 'f2-profile-unexpected' ? new \LogicException('PRIVATE_TEST_SQL_DETAIL unexpected') : $failure;
                    $_POST += ['name' => 'Test User', 'email' => 'test@example.invalid'];
                    $ctrl->updateProfile();
                    check($ctrl->rendered[1]['error'] === 'Unable to update your profile. Please try again.', 'Profile handler hides infrastructure error');
                }
                check($auth->id() === 1, 'Account failure retains authentication');
                renderAccount($ctrl->rendered[1]);
            }
            check(!str_contains(json_encode($ctrl->rendered), 'PRIVATE_TEST_SQL_DETAIL'), 'Raw failure absent from user data');
            check(count(\App\Core\Logger::$messages) > 0 && !str_contains(implode(' ', \App\Core\Logger::$messages), 'PRIVATE_TEST_SQL_DETAIL'), 'Infrastructure failure logged without raw details');
        } elseif (str_starts_with($scenario, 'f3-')) {
            $isLink = str_ends_with($scenario, 'link');
            if ($isLink) { $session->put('auth_user_id', 1); }
            $oauthState = $state->generate($isLink ? 'link' : 'login');
            $_GET = ['state' => $oauthState, 'code' => 'test-code'];
            if (str_contains($scenario, 'dependency')) {
                $googleTestEnvironment = ['GOOGLE_CLIENT_ID' => 'test-client', 'GOOGLE_CLIENT_SECRET' => 'test-placeholder', 'GOOGLE_REDIRECT_URI' => 'https://kobi.example.invalid/callback'];
                check(!class_exists('Google\\Client'), 'SDK deliberately absent in database-free harness');
            }
            // Real container and production service: no FakeGoogle or replacement initialization method.
            $dependencies[GoogleAuthService::class] = (new Container())->get(GoogleAuthService::class);
            $ctrl = controller(TestGoogleController::class, $dependencies);
            $safeMessage = $isLink ? 'Unable to connect Google. Please try again.' : 'Unable to sign in with Google. Please try again.';
            $verifyFailure = function () use ($session): void {
                check(!$session->has('google_oauth_state') && !$session->has('google_oauth_intent'), 'Initialization failure clears OAuth state and intent');
                check(count(\App\Core\Logger::$messages) === 1, 'Initialization failure diagnosed');
                check(!str_contains(implode(' ', \App\Core\Logger::$messages), 'test-placeholder'), 'Configuration values absent from log');
            };
            $redirectCheck = function ($path) use ($isLink, $safeMessage, $verifyFailure, $auth, $session, $dependencies): void {
                check($isLink && $path === '/account', 'Initialization failure preserves link destination');
                $verifyFailure();
                check($auth->id() === 1 && $session->get('google_auth_error') === $safeMessage && $session->regenerations === 0, 'Initialization failure preserves authenticated account and safe flash');
                $account = controller(TestAccountController::class, $dependencies); $account->index();
                check($account->rendered[1]['error'] === $safeMessage, 'Account renders initialization error safely');
                renderAccount($account->rendered[1]);
            };
            if (str_contains($scenario, 'callback')) { $ctrl->callback(); }
            elseif ($isLink) { $ctrl->link(); }
            else { $ctrl->redirect(); }
            check(!$isLink, 'Link initialization failure must redirect');
            $verifyFailure();
            check($ctrl->rendered === ['auth/login', ['title' => 'Login', 'error' => $safeMessage]] && !$auth->check(), 'Initialization failure preserves safe login context');
        } elseif (str_starts_with($scenario, 'reset-')) {
            $_POST += ['token' => $raw, 'password' => 'replacement-test-password', 'password_confirmation' => 'replacement-test-password'];
            if ($scenario === 'reset-short') { $_POST['password'] = $_POST['password_confirmation'] = 'short'; }
            if ($scenario === 'reset-mismatch') { $_POST['password_confirmation'] = 'different-test-password'; }
            if ($scenario === 'reset-expired') { $repository->rows[1]['expires_at'] = '2000-01-01 00:00:00'; }
            if ($scenario === 'reset-csrf') { $_POST['_csrf_token'] = 'wrong-csrf'; }
            $redirectCheck = function ($path) use ($passwordUpdated, $session, $tokens, $raw): void {
                check($path === '/login', 'Reset redirects to login');
                $passwordUpdated(); check(!$session->has('auth_user_id'), 'Reset does not authenticate');
                fails(fn () => $tokens->validatePasswordResetToken($raw), 'Invalid or expired token.');
                fails(fn () => $tokens->validatePasswordResetToken(str_repeat('b', 64)), 'Invalid or expired token.');
            };
            $ctrl = controller(TestAuthController::class, $dependencies);
            $ctrl->resetPasswordSubmit();
            check($scenario !== 'reset-success', 'Success must redirect');
            check($users->updates === 0 && $repository->rows[1]['used_at'] === null, 'Failure preserves token/password');
            check($transaction->begins === 0, 'Reset validation failure never starts password transaction');
            $expected = match ($scenario) {
                'reset-short' => 'New password must be at least 8 characters long.',
                'reset-mismatch' => 'New passwords do not match.',
                'reset-csrf' => 'Invalid security token. Please try again.',
                default => 'Invalid or expired token.'
            };
            check($ctrl->rendered[1]['error'] === $expected, 'Reset error unchanged');
            if (in_array($scenario, ['reset-short', 'reset-mismatch', 'reset-csrf'], true)) { $tokens->validatePasswordResetToken($raw); }
        } elseif (str_starts_with($scenario, 'change-')) {
            $session->put('auth_user_id', 1);
            if ($scenario === 'change-no-tokens') { $repository->invalidateUnusedTokens(1, 'password_reset'); }
            if ($scenario === 'change-wrong' || $scenario === 'change-mismatch') {
                fails(fn () => $auth->changePassword(
                    $scenario === 'change-wrong' ? 'wrong-test-password' : 'original-test-password',
                    'replacement-test-password', $scenario === 'change-mismatch' ? 'different' : 'replacement-test-password'
                ), $scenario === 'change-wrong' ? 'Current password is incorrect.' : 'New passwords do not match.');
                check($users->updates === 0 && $repository->rows[1]['used_at'] === null, 'Failed change preserves password/tokens');
            } else {
                $auth->changePassword('original-test-password', 'replacement-test-password', 'replacement-test-password');
                $passwordUpdated(); check($auth->id() === 1 && $session->regenerations === 1, 'Change retains regenerated session');
            }
        } elseif ($scenario === 'email-change') {
            $session->put('auth_user_id', 1);
            $auth->updateProfile('Test User', 'changed@example.invalid');
            check($users->rows[1]['email_verified_at'] === null && $auth->id() === 1, 'Email change resets verification and retains session');
            $email->send(1);
            check($repository->rows[3]['used_at'] !== null && $repository->rows[1]['used_at'] === null, 'Only old verification tokens invalidated');
            check(count($mail->messages) === 1 && $mail->messages[0]['to'] === 'changed@example.invalid', 'Verification sent to new address');
            preg_match('/token=([a-f0-9]{64})/', $mail->messages[0]['htmlBody'], $match);
            $record = $tokens->consumeEmailVerificationToken($match[1]);
            $auth->verifyEmailAddress($record['user_id']);
            check($users->rows[1]['email_verified_at'] !== null && $auth->id() === 1, 'New verification works');
            fails(fn () => $tokens->validateEmailVerificationToken(str_repeat('c', 64)), 'Invalid or expired token.');
        } elseif (str_starts_with($scenario, 'oauth-')) {
            $link = str_contains($scenario, 'link');
            if ($link) { $session->put('auth_user_id', 1); }
            $oauthState = $state->generate($link ? 'link' : 'login');
            $_GET = ['state' => $oauthState, 'code' => 'test-code'];
            if (str_contains($scenario, 'cancel')) { $_GET['error'] = 'access_denied'; }
            if ($scenario === 'oauth-invalid-link') { $_GET['state'] = 'invalid'; }
            if ($scenario === 'oauth-missing-state') { unset($_GET['state']); }
            if (str_contains($scenario, 'internal')) { $google->failure = new \LogicException('Internal provider failure'); }
            if ($scenario === 'oauth-new') { $users->rows = []; $identities->rows = []; }
            if ($scenario === 'oauth-collision' || $scenario === 'oauth-link-success') { $identities->rows = []; }
            $expectedRedirect = match ($scenario) {
                'oauth-existing', 'oauth-new' => '/',
                'oauth-link-success' => '/account?google_linked=1',
                'oauth-cancel-link', 'oauth-invalid-link', 'oauth-internal-link', 'oauth-link-initiation' => '/account',
                default => null
            };
            $expectedError = match ($scenario) {
                'oauth-cancel-login', 'oauth-cancel-link' => 'Google sign-in was cancelled or could not be completed.',
                'oauth-invalid-link', 'oauth-missing-state' => 'Invalid Google authentication request.',
                'oauth-internal-login' => 'Unable to sign in with Google. Please try again.',
                'oauth-internal-link', 'oauth-link-initiation' => 'Unable to connect Google. Please try again.',
                'oauth-collision' => 'A KOBI account already exists with this Google email address. Please sign in to your KOBI account first. Google linking will be available from your account settings.',
                default => null
            };
            $clear = function () use ($session): void {
                check(!$session->has('google_oauth_state') && !$session->has('google_oauth_intent'), 'OAuth state consumed');
            };
            $providerCheck = function () use ($scenario, $google): void {
                $noExchange = in_array($scenario, ['oauth-cancel-login', 'oauth-cancel-link', 'oauth-invalid-link', 'oauth-missing-state', 'oauth-link-initiation'], true);
                check($google->calls === ($noExchange ? 0 : 1), 'Expected provider exchange count');
                $providerFailure = in_array($scenario, ['oauth-internal-login', 'oauth-internal-link', 'oauth-link-initiation'], true);
                check($google->exceptions === ($providerFailure ? 1 : 0), 'Expected provider exception count');
                check($google->authorizationCalls === ($scenario === 'oauth-link-initiation' ? 1 : 0), 'Expected authorization URL call count');
            };
            $redirectCheck = function ($path) use ($scenario, $clear, $providerCheck, $expectedRedirect, $expectedError, $session, $auth, $users, $identities, $profiles, $dependencies): void {
                check($expectedRedirect !== null && $path === $expectedRedirect, 'Exact Google redirect required');
                $clear();
                $providerCheck();
                if (in_array($scenario, ['oauth-existing', 'oauth-new'], true)) {
                    check($auth->id() === 1 && $session->regenerations === 1, 'Google login authenticates and regenerates session');
                    if ($scenario === 'oauth-new') {
                        check(count($users->rows) === 1 && isset($users->rows[1]) && $users->rows[1]['password_set_at'] === null, 'New Google-only user exists');
                        $identity = $identities->findByProviderUserId('google', 'subject');
                        check(count($identities->rows) === 1 && $identity !== null && $identity['user_id'] === $auth->id(), 'New Google identity belongs to authenticated user');
                        check($profiles->created === 1, 'New health profile created');
                    }
                } elseif ($scenario === 'oauth-link-success') {
                    $identity = $identities->findByProviderUserId('google', 'subject');
                    check(count($identities->rows) === 1 && $identity !== null && $identity['user_id'] === 1 && $auth->id() === 1, 'Identity linked to authenticated KOBI user');
                } else {
                    check($auth->id() === 1 && $session->get('google_auth_error') === $expectedError, 'Exact safe link error and authenticated account context');
                    $account = controller(TestAccountController::class, $dependencies);
                    $account->index();
                    check($account->rendered[0] === 'account/index' && $account->rendered[1]['error'] === $expectedError, 'Account displays safe Google error');
                    renderAccount($account->rendered[1]);
                    check(!$session->has('google_auth_error'), 'Link error consumed on account');
                }
            };
            $ctrl = controller(TestGoogleController::class, $dependencies);
            if ($scenario === 'oauth-link-initiation') { $ctrl->link(); } else { $ctrl->callback(); }
            // Returning is valid only for scenarios explicitly expecting a login error view.
            check($expectedRedirect === null, 'Expected Google redirect did not occur');
            $clear();
            $providerCheck();
            check($ctrl->rendered[0] === 'auth/login' && $ctrl->rendered[1]['error'] === $expectedError, 'Exact safe login error context');
            check(!$auth->check(), 'Login error does not authenticate');
            if ($scenario === 'oauth-collision') {
                check(count($users->rows) === 1 && $users->rows[1]['email'] === 'test@example.invalid' && $identities->rows === [], 'No new account or automatic identity merge');
            }
        } elseif (str_starts_with($scenario, 'unlink-')) {
            if ($scenario === 'unlink-only' || $scenario === 'unlink-after-reset') { $users->rows[1]['password_set_at'] = null; }
            if ($scenario === 'unlink-after-reset') {
                $session->put('auth_user_id', 1);
                $auth->resetPassword(1, 'replacement-test-password', 'replacement-test-password');
                check(!$auth->check(), 'Successful reset removes existing authentication');
            }
            if ($scenario === 'unlink-only') {
                fails(fn () => $identityService->unlink(1, 1), 'You cannot disconnect your only authentication method. Please set a KOBI password first.');
                check(count($identities->rows) === 1, 'Only identity retained');
            } elseif ($scenario === 'unlink-other-user') {
                fails(fn () => $identityService->unlink(2, 1), 'Authentication identity not found.');
                check(count($identities->rows) === 1, 'Other identity protected');
            } else { check($identityService->unlink(1, 1) && $identities->rows === [], 'Disconnect succeeds'); }
        } else {
            $session->put('auth_user_id', 1);
            $ctrl = controller(TestAccountController::class, $dependencies);
            if ($scenario === 'account-profile-anonymous') {
                $session->remove('auth_user_id');
                $redirectCheck = static fn(string $path) => check($path === '/login', 'Anonymous profile update redirects to login');
                $ctrl->updateProfile();
            } elseif ($scenario === 'account-profile-error') {
                $before = $users->rows;
                foreach ([
                    ['', 'test@example.invalid', 'Name is required.'],
                    ['   ', 'test@example.invalid', 'Name is required.'],
                    ['<b>Test</b>', 'invalid-email', 'Please provide a valid email address.']
                ] as [$name, $email, $message]) {
                    $_POST['name'] = $name;
                    $_POST['email'] = $email;
                    $ctrl->updateProfile();
                    $data = $ctrl->rendered[1];
                    check($data['error'] === $message, 'Profile validation message unchanged');
                    check($data['profileForm'] === ['name' => $name, 'email' => $email], 'Failed profile input retained separately');
                    check($users->rows === $before, 'Failed profile validation does not persist input');
                    $html = renderAccount($data);
                    check(str_contains($html, 'value="' . e($name) . '"') && str_contains($html, 'value="' . e($email) . '"'), 'Retained profile input escaped in form');
                    check(!str_contains($html, '<b>Test</b>'), 'Retained input cannot render HTML');
                }
                $_POST['_csrf_token'] = 'invalid';
                $_POST['name'] = 'Valid Test Name';
                $_POST['email'] = 'test@example.invalid';
                $ctrl->updateProfile();
                check($ctrl->rendered[1]['error'] === 'Invalid security token. Please try again.', 'Profile CSRF rejection unchanged');
                check($users->rows === $before, 'Profile CSRF failure cannot persist input');
            } elseif ($scenario === 'account-password-error') {
                $_POST += ['current_password' => 'wrong', 'new_password' => 'replacement-test-password', 'new_password_confirmation' => 'replacement-test-password'];
                $ctrl->changePassword();
            } elseif ($scenario === 'account-unlink-error') {
                $users->rows[1]['password_set_at'] = null; $_POST['identity_id'] = 1; $ctrl->unlinkAuthentication();
            } else { $session->put('google_auth_error', 'Unable to connect Google. Please try again.'); $ctrl->index(); }
            [$view, $data] = $ctrl->rendered;
            check($view === 'account/index' && $data['user']['id'] === 1 && count($data['identities']) === 1, 'Account data complete');
            check($data['hasPassword'] === ($scenario !== 'account-unlink-error'), 'Password controls accurate');
            check(!$session->has('google_auth_error'), 'Google error flash consumed');
            renderAccount($data);
        }
        echo "PASS\n";
    } catch (\Throwable $error) {
        fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . "\n");
        exit(1);
    }
}
