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

                bp.id,

                bp.name_en,

                bp.gender,

                bp.slug,

                COUNT(d.id) AS disease_count

            FROM body_parts bp

            LEFT JOIN diseases d

            ON d.body_part_id = bp.id

            GROUP BY

                bp.id,

                bp.name_en

            ORDER BY

                bp.name_en
        ");
    }
}