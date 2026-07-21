<?php

declare(strict_types=1);

namespace App\Repositories;

class HomeRepository extends BaseRepository
{

    public function searchDiseases(
        string $keyword
    ): array
    {
        return $this->fetchAll(

            "
            SELECT

                slug,

                disease_en

            FROM diseases

            WHERE disease_en LIKE ?

            ORDER BY disease_en ASC

            LIMIT 8
            ",

            [

                '%' . $keyword . '%'

            ]

        );
    }

    public function getBodySystems(): array
    {
        return $this->fetchAll(

            "
            SELECT

                bp.id,

                bp.name_en,

                bp.slug,

                COUNT(d.id) AS disease_count

            FROM body_parts bp

            LEFT JOIN diseases d

                ON d.body_part_id = bp.id

            GROUP BY

                bp.id,
                bp.name_en,
                bp.slug

            ORDER BY
                bp.name_en ASC
            "
        );
    }
}