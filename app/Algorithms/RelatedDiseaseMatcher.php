<?php

declare(strict_types=1);

namespace App\Algorithms;

final class RelatedDiseaseMatcher
{
    /**
     * Weight given to diseases from the same body part.
     */
    private const BODY_PART_BONUS = 25;

    /**
     * Maximum score allowed.
     */
    private const MAX_SCORE = 100;

    /**
     * Rank related diseases.
     *
     * @param array $currentDisease
     * @param array $relatedDiseases
     *
     * @return array
     */
    public function rank(
        array $currentDisease,
        array $relatedDiseases
    ): array {

        $currentSymptoms = array_column(
            $currentDisease['symptoms'] ?? [],
            'id'
        );

        $totalSymptoms = count($currentSymptoms);

        if ($totalSymptoms === 0) {
            return [];
        }

        foreach ($relatedDiseases as &$disease) {

           $candidateSymptoms = $disease['symptoms'] ?? [];

            $candidateSymptomIds = array_column(
                $candidateSymptoms,
                'id'
            );

            $sharedIds = array_values(
                array_intersect(
                    $currentSymptoms,
                    $candidateSymptomIds
                )
            );

            $sharedCount = count($sharedIds);

            /*
            |--------------------------------------------------------------------------
            | Build shared symptom names
            |--------------------------------------------------------------------------
            */

            $sharedSymptoms = [];

            foreach ($candidateSymptoms as $symptom) {

                if (
                    in_array(
                        $symptom['id'],
                        $sharedIds,
                        true
                    )
                ) {
                    $sharedSymptoms[] = $symptom['symptom_en'];
                }
            }

            $score = ($sharedCount / $totalSymptoms) * 75;

            if (
                isset($currentDisease['body_part_id']) &&
                isset($disease['body_part_id']) &&
                $currentDisease['body_part_id'] == $disease['body_part_id']
            ) {
                $score += self::BODY_PART_BONUS;
            }

            if ($score > self::MAX_SCORE) {
                $score = self::MAX_SCORE;
            }

            $disease['similarity_score'] = (int) round($score);

            $disease['shared_symptom_count'] = $sharedCount;

            $disease['shared_symptom_ids'] = $sharedIds;

            $disease['shared_symptoms'] = $sharedSymptoms;
            
        }

        unset($disease);

        usort(
            $relatedDiseases,
            function (array $a, array $b): int {

                if (
                    $a['similarity_score'] ===
                    $b['similarity_score']
                ) {
                    return strcmp(
                        $a['disease_en'],
                        $b['disease_en']
                    );
                }

                return $b['similarity_score']
                    <=>
                    $a['similarity_score'];
            }
        );

        return $relatedDiseases;
    }
}