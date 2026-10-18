<?php
declare(strict_types=1);
namespace App\Services;

class ApiAuthClock
{
    public function now(): int { return time(); }
}
