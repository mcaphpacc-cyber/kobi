<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class PublicApiResponse
{
    private static ?int $bufferLevel = null;

    /** Keep unexpected output out of the API, without buffering ordinary web requests. */
    public static function begin(): void
    {
        ob_start();
        self::$bufferLevel = ob_get_level();
    }

    public static function success(array $data, array $meta = []): void
    {
        self::send(['success' => true, 'data' => $data, 'meta' => ['api_version' => 'v1'] + $meta], 200);
    }

    public static function error(int $status, string $code, string $message, array $fields = [], string $allow = 'GET'): void
    {
        $error = ['code' => $code, 'message' => $message];
        if ($fields !== []) { $error['fields'] = $fields; }
        self::send(['success' => false, 'error' => $error, 'meta' => ['api_version' => 'v1']], $status, $allow);
    }

    private static function send(array $body, int $status, string $allow = 'GET'): void
    {
        try {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (strlen($json) > 262144) { throw new \LengthException('API response exceeds limit'); }
        } catch (Throwable $exception) {
            Logger::error('Public API serialization failed: ' . get_class($exception));
            $status = 500;
            $json = '{"success":false,"error":{"code":"INTERNAL_ERROR","message":"Unable to complete the request."},"meta":{"api_version":"v1"}}';
        }
        if (self::$bufferLevel !== null) {
            while (ob_get_level() >= self::$bufferLevel) { ob_end_clean(); }
            self::$bufferLevel = null;
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        if ($status === 405) { header('Allow: ' . $allow); }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') { echo $json; }
    }
}
