<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class MailService
{
    /**
     * Send an HTML email.
     *
     * In development, the "log" driver writes the complete email
     * to storage/logs/mail.log so verification/reset links can be tested.
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): void {
        $to = trim($to);
        $subject = trim($subject);

        if ($to === '') {
            throw new RuntimeException('Recipient email address is required.');
        }

        if ($subject === '') {
            throw new RuntimeException('Email subject is required.');
        }

        $mailConfig = config('mail');

        if (!is_array($mailConfig)) {
            throw new RuntimeException('Mail configuration is unavailable.');
        }

        $driver = strtolower((string) ($mailConfig['driver'] ?? 'log'));

        if ($driver === 'log') {
            $this->logEmail(
                $to,
                $subject,
                $htmlBody,
                $textBody
            );

            return;
        }

        if ($driver !== 'smtp') {
            throw new RuntimeException(
                'Unsupported mail driver: ' . $driver
            );
        }

        $this->sendViaSmtp(
            $mailConfig,
            $to,
            $subject,
            $htmlBody,
            $textBody
        );
    }

    /**
     * Send an email using SMTP through PHPMailer.
     *
     * @param array<string, mixed> $mailConfig
     */
    private function sendViaSmtp(
        array $mailConfig,
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody
    ): void {
        $fromAddress = trim(
            (string) ($mailConfig['from_address'] ?? '')
        );

        $fromName = trim(
            (string) ($mailConfig['from_name'] ?? 'KOBI')
        );

        if ($fromAddress === '') {
            throw new RuntimeException(
                'MAIL_FROM_ADDRESS is not configured.'
            );
        }

        $host = trim(
            (string) ($mailConfig['host'] ?? '')
        );

        $username = trim(
            (string) ($mailConfig['username'] ?? '')
        );

        $password = (string) (
            $mailConfig['password'] ?? ''
        );

        $port = (int) (
            $mailConfig['port'] ?? 587
        );

        $encryption = strtolower(
            trim((string) (
                $mailConfig['encryption'] ?? 'tls'
            ))
        );

        if ($host === '') {
            throw new RuntimeException(
                'MAIL_HOST is not configured.'
            );
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();

            $mail->Host = $host;
            $mail->Port = $port;

            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;

            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption !== '' && $encryption !== 'none') {
                throw new RuntimeException(
                    'Unsupported mail encryption: ' . $encryption
                );
            }

            $mail->CharSet = 'UTF-8';

            $mail->setFrom(
                $fromAddress,
                $fromName !== '' ? $fromName : 'KOBI'
            );

            $mail->addAddress($to);

            $mail->Subject = $subject;

            $mail->isHTML(true);
            $mail->Body = $htmlBody;

            if ($textBody !== null && trim($textBody) !== '') {
                $mail->AltBody = $textBody;
            } else {
                $mail->AltBody = $this->htmlToText($htmlBody);
            }

            $mail->send();
        } catch (PHPMailerException $exception) {
            throw new RuntimeException(
                'Unable to send email.',
                0,
                $exception
            );
        }
    }

    /**
     * Log an email during local development.
     *
     * The complete message is written so verification and password
     * reset links can be tested without an SMTP server.
     */
    private function logEmail(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody
    ): void {
        $storagePath = (string) config('storage_path');

        if ($storagePath === '') {
            throw new RuntimeException(
                'Storage path is not configured.'
            );
        }

        $logDirectory = rtrim($storagePath, '/\\') . '/logs';

        if (!is_dir($logDirectory)) {
            if (!mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
                throw new RuntimeException(
                    'Unable to create mail log directory.'
                );
            }
        }

        $logFile = $logDirectory . '/mail.log';

        $entry = sprintf(
            "\n[%s]\nTO: %s\nSUBJECT: %s\n\nHTML:\n%s\n\nTEXT:\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $htmlBody,
            $textBody ?? $this->htmlToText($htmlBody),
            str_repeat('=', 80)
        );

        $result = file_put_contents(
            $logFile,
            $entry,
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            throw new RuntimeException(
                'Unable to write mail log.'
            );
        }
    }

    /**
     * Convert simple HTML email content to plain text.
     */
    private function htmlToText(string $html): string
    {
        $text = strip_tags(
            str_replace(
                ['<br>', '<br/>', '<br />', '</p>', '</div>'],
                "\n",
                $html
            )
        );

        return trim(
            html_entity_decode(
                $text,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );
    }
}