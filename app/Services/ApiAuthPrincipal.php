<?php
declare(strict_types=1);
namespace App\Services;

final class ApiAuthPrincipal
{
    public function __construct(public readonly int $userId, public readonly int $sessionId) {}
}
