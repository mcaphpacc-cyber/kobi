<?php

namespace App\Services\Disease;

class QuickFactsBuilder
{
    public static function build(array $disease): array
    {
        $facts = [];

        self::addFact(
            $facts,
            'cause',
            'Cause',
            'bi-bug',
            $disease['cause'] ?? null
        );

        self::addFact(
            $facts,
            'risk',
            'Risk Factors',
            'bi-exclamation-triangle',
            $disease['risk_factors'] ?? null
        );

        self::addFact(
            $facts,
            'diagnosis',
            'Diagnosis',
            'bi-heart-pulse',
            $disease['diagnosis'] ?? null
        );

        self::addFact(
            $facts,
            'prevention',
            'Prevention',
            'bi-shield-check',
            $disease['prevention'] ?? null
        );

        return $facts;
    }

    private static function addFact(
        array &$facts,
        string $type,
        string $title,
        string $icon,
        ?string $value
    ): void
    {
        if (empty($value)) {
            return;
        }

        $facts[] = [

            'type' => $type,

            'title' => $title,

            'icon' => $icon,

            'preview' => self::preview($value)

        ];
    }

    private static function preview(string $text): string
    {
        $text = trim(strip_tags($text));

        if (mb_strlen($text) <= 80) {
            return $text;
        }

        return mb_substr($text, 0, 80) . '...';
    }
}