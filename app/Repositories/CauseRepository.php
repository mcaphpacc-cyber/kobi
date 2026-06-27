<?php

declare(strict_types=1);

namespace App\Repositories;

class CauseRepository extends BaseRepository
{
    public function count(): int
    {
        return $this->countRecords('causes');
    }
}