<?php

declare(strict_types=1);

namespace App\Algorithms;

final class DiseaseComparisonBuilder
{
    /**
     * Build comparison view model.
     */
    public function build(
        array $left,
        array $right
    ): array {

        return [

            'left' => $left,

            'right' => $right,

            'symptoms' => $this->compareItems(
                $left['symptoms'] ?? [],
                $right['symptoms'] ?? [],
                'symptom_en'
            ),

            'causes' => $this->compareItems(
                $left['causes'] ?? [],
                $right['causes'] ?? [],
                'cause_en'
            )

        ];
    }

    /**
     * Compare two collections.
     */
    private function compareItems(
        array $leftItems,
        array $rightItems,
        string $nameKey
    ): array {

        $leftMap = [];
        $rightMap = [];

        foreach ($leftItems as $item) {

            $leftMap[$item['id']] = $item;

        }

        foreach ($rightItems as $item) {

            $rightMap[$item['id']] = $item;

        }

        $shared = [];
        $leftOnly = [];
        $rightOnly = [];

        foreach ($leftMap as $id => $item) {

            if (isset($rightMap[$id])) {

                $shared[] = $item;

            } else {

                $leftOnly[] = $item;

            }

        }

        foreach ($rightMap as $id => $item) {

            if (!isset($leftMap[$id])) {

                $rightOnly[] = $item;

            }

        }

        usort(
            $shared,
            fn($a, $b) => strcmp(
                $a[$nameKey],
                $b[$nameKey]
            )
        );

        usort(
            $leftOnly,
            fn($a, $b) => strcmp(
                $a[$nameKey],
                $b[$nameKey]
            )
        );

        usort(
            $rightOnly,
            fn($a, $b) => strcmp(
                $a[$nameKey],
                $b[$nameKey]
            )
        );

        return [

            'shared' => $shared,

            'left_only' => $leftOnly,

            'right_only' => $rightOnly

        ];
    }
}