<?php

declare(strict_types=1);

namespace App\Algorithms;

class SymptomMatcher
{
     private const USER_MATCH_WEIGHT = 0.70;

    private const COVERAGE_WEIGHT = 0.30;

    private const MAX_RESULTS = 10;

    private const STRONG_MATCH = 80;

    private const MODERATE_MATCH = 50;

    private const MAX_MISSING_SYMPTOMS = 3;

    public function match(
        array $selectedSymptoms,
        array $candidateDiseases,
    ): array {

        $results = [];

        foreach ($candidateDiseases as $disease) {

            $matched = [];
            $missing = [];

            foreach ($disease['symptoms'] as $symptom) {

                if (
                    in_array(
                        $symptom['id'],
                        $selectedSymptoms,
                        true
                    )
                ) {

                    $matched[] = $symptom;

                } else {

                    $missing[] = $symptom;

                }

            }

            $matchedCount = count($matched);

            if ($matchedCount === 0) {
                continue;
            }

            $userMatchScore = round(
                ($matchedCount / count($selectedSymptoms)) * 100
            );

            $coverage = round(
                ($matchedCount / count($disease['symptoms'])) * 100
            );

            $rankingScore =
                ($userMatchScore * self::USER_MATCH_WEIGHT)
                +
                ($coverage * self::COVERAGE_WEIGHT);

            $results[] = [

                'disease' => $disease,

                'matchedSymptoms' => $matched,

                'missingSymptoms' => $missing,

                'matchedCount' => $matchedCount,

                'userMatchScore' => $userMatchScore,

                'coverage' => $coverage,

                'rankingScore' => round($rankingScore, 2)

            ];

        }

        usort(
            $results,
            fn ($a, $b)
                => $b['rankingScore']
                <=> $a['rankingScore']
        );

        return [

            'summary' => [

                'selectedSymptoms' => $selectedSymptoms,

                'totalMatches' => count($results),

                'displayedMatches' => min(
                    count($results),
                    self::MAX_RESULTS
                ),

                'generatedAt' => date('c')

            ],

            'results' => array_slice(
                $results,
                0,
                self::MAX_RESULTS
            )

        ];
    }

    private function getMatchLevel(
        int $score
    ): string {

        if ($score >= self::STRONG_MATCH) {
            return 'strong';
        }

        if ($score >= self::MODERATE_MATCH) {
            return 'moderate';
        }

        return 'limited';
    }
}