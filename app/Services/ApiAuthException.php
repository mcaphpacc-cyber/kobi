<?php
declare(strict_types=1);
namespace App\Services;

final class ApiAuthException extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, string $message, public readonly int $retryAfter = 0)
    { parent::__construct($message); }

    public static function denied(): self
    { return new self(401, 'UNAUTHENTICATED', 'Authentication is required. Please sign in again.'); }
}
