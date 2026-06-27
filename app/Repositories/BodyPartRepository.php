<?php

declare(strict_types=1);

namespace App\Repositories;

class BodyPartRepository extends BaseRepository
{
    public function count(): int
    {
        return $this->countRecords('body_parts');
    }

    public function getAll(): array
    {
        return $this->fetchAll("
            SELECT
                id,
                name_en,
                name_hi,
                gender,
                slug
            FROM body_parts
            ORDER BY name_en
        ");
    }
}