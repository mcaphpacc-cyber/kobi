<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Logger
{
    private const LOG_FILE = 'app.log';

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    public static function warning(string $message): void
    {
        self::write('WARNING', $message);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function exception(Throwable $exception): void
    {
        $message = sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        self::write('EXCEPTION', $message);
    }

    private static function write(
        string $level,
        string $message
    ): void {
        $logDirectory =
            dirname(__DIR__, 2) . '/storage/logs';

        $logFile =
            $logDirectory . '/' . self::LOG_FILE;

        try {
            if (!is_dir($logDirectory)) {
                mkdir($logDirectory, 0755, true);
            }

            $timestamp =
                date('Y-m-d H:i:s');

            $entry =
                sprintf(
                    "[%s] %s: %s%s",
                    $timestamp,
                    $level,
                    $message,
                    PHP_EOL
                );

            file_put_contents(
                $logFile,
                $entry,
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable) {
            /*
             * Logging must never cause another application failure.
             *
             * Intentionally ignore logger failures.
             */
        }
    }
}