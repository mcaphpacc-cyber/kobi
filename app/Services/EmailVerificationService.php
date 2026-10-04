<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use RuntimeException;

class EmailVerificationService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserAuthTokenService $userAuthTokenService,
        private MailService $mailService
    ) {
    }

    /**
     * Send an email verification message to a user.
     *
     * The user's account must be active and unverified.
     */
    public function send(int $userId): void
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

        if ($user['email_verified_at'] !== null) {
            throw new RuntimeException(
                'This email address is already verified.'
            );
        }

        $token = $this->userAuthTokenService
            ->createEmailVerificationToken($userId);

        $verificationUrl = url(
            '/verify-email?token=' .
            rawurlencode($token)
        );

        $this->mailService->send(
            (string) $user['email'],
            'Verify your KOBI email address',
            $this->buildHtml(
                (string) $user['name'],
                $verificationUrl
            ),
            $this->buildText(
                (string) $user['name'],
                $verificationUrl
            )
        );
    }

    /**
     * Build the HTML verification email.
     */
    private function buildHtml(
        string $name,
        string $verificationUrl
    ): string {
        $safeName = e($name);
        $safeUrl = e($verificationUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Verify your KOBI email</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:Arial,sans-serif;color:#212529;">
    <div style="max-width:600px;margin:40px auto;padding:30px;background:#ffffff;border-radius:8px;">
        <h2 style="margin-top:0;">Welcome to KOBI</h2>

        <p>Hello {$safeName},</p>

        <p>
            Thank you for using KOBI.
            Please verify your email address to continue.
        </p>

        <p style="margin:30px 0;">
            <a
                href="{$safeUrl}"
                style="display:inline-block;padding:12px 20px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px;"
            >
                Verify Email Address
            </a>
        </p>

        <p>
            If the button does not work, copy and paste the following
            address into your browser:
        </p>

        <p style="word-break:break-all;">
            {$safeUrl}
        </p>

        <p>
            This verification link expires after 24 hours.
        </p>

        <p>
            If you did not request this email, you can safely ignore it.
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
     * Build the plain-text verification email.
     */
    private function buildText(
        string $name,
        string $verificationUrl
    ): string {
        return
            "Hello {$name},\n\n" .
            "Thank you for using KOBI.\n" .
            "Please verify your email address to continue.\n\n" .
            "Verify your email address using this link:\n\n" .
            "{$verificationUrl}\n\n" .
            "This verification link expires after 24 hours.\n\n" .
            "If you did not request this email, you can safely ignore it.\n\n" .
            "Regards,\n" .
            "KOBI Team";
    }
}