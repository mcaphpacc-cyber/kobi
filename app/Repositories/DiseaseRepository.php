<?php

declare(strict_types=1);

namespace App\Repositories;

class DiseaseRepository extends BaseRepository
{
    /**
     * Get all diseases ordered alphabetically.
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            ORDER BY disease_en
        ";

        return $this->fetchAll($sql);
    }

    /**
     * Find disease by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'id' => $id
        ]);
    }

    /**
     * Find disease by slug.
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            WHERE slug = :slug
            LIMIT 1
        ";

        return $this->fetch($sql, [
            'slug' => $slug
        ]);
    }

    /**
     * Search diseases.
     */
    public function search(string $keyword): array
    {
        $sql = "
            SELECT
                id,
                body_part_id,
                disease_en,
                disease_hi,
                slug,
                gender,
                icd_code,
                icd10_code
            FROM diseases
            WHERE
                disease_en LIKE :keyword
                OR disease_hi LIKE :keyword
            ORDER BY disease_en
        ";

        return $this->fetchAll($sql, [
            'keyword' => '%' . $keyword . '%'
        ]);
    }

    public function count(): int
    {
        return $this->countRecords('diseases');
    }

    public function getFeatured(int $limit = 8): array
    {
        $sql = "
            SELECT
                d.id,
                d.disease_en,
                d.disease_hi,
                d.slug
            FROM featured_diseases fd
            INNER JOIN diseases d
                ON d.id = fd.disease_id
            ORDER BY fd.priority_order ASC
            LIMIT :limit
        ";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':limit',
            $limit,
            \PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }
    public function findBySymptoms(array $symptomIds): array
    {
        if (empty($symptomIds)) {
            return [];
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($symptomIds), '?')
        );

        $sql = "
            SELECT
                d.id,
                d.disease_en,
                d.slug,
                COUNT(*) AS matched
            FROM diseases d
            INNER JOIN disease_symptoms ds
                ON ds.disease_id = d.id
            WHERE ds.symptom_id IN ($placeholders)
            GROUP BY d.id
            ORDER BY matched DESC,
                    d.disease_en
        ";

        return $this->fetchAll(
            $sql,
            $symptomIds
        );
    }

    public function getSymptomsForDisease(int $diseaseId): array
    {
        return [];
    }

    public function findFeatured(): array
    {
        return [];
    }

    public function getCandidateDiseases(array $symptomIds): array
{
    if (empty($symptomIds)) {
        return [];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($symptomIds), '?')
    );

    $sql = "
        SELECT DISTINCT
            d.id,
            d.disease_en,
            d.slug,
            d.severity_level,
            d.urgency_note
        FROM diseases d
        INNER JOIN disease_symptoms ds
            ON ds.disease_id = d.id
        WHERE ds.symptom_id IN ($placeholders)
        ORDER BY d.disease_en
    ";

    $diseases = $this->fetchAll(
        $sql,
        $symptomIds
    );

    if (empty($diseases)) {
        return [];
    }

    $diseaseIds = array_column(
        $diseases,
        'id'
    );

    $symptomMap =
        $this->getSymptomsForDiseases(
            $diseaseIds
        );

    foreach ($diseases as &$disease) {

        $disease['symptoms'] =
            $symptomMap[$disease['id']] ?? [];

    }

    return $diseases;
}

    private function getSymptomsForDiseases(
        array $diseaseIds
    ): array {

        if (empty($diseaseIds)) {
            return [];
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($diseaseIds), '?')
        );

        $sql = "
            SELECT

                ds.disease_id,

                s.id,

                s.symptom_en

            FROM disease_symptoms ds

            INNER JOIN symptoms s

                ON s.id = ds.symptom_id

            WHERE ds.disease_id IN ($placeholders)

            ORDER BY
                ds.disease_id,
                s.symptom_en
        ";

        $rows = $this->fetchAll(
            $sql,
            $diseaseIds
        );

        return $this->groupSymptomsByDisease(
            $rows
        );
    }

    private function groupSymptomsByDisease(
        array $rows
    ): array {

        $grouped = [];

        foreach ($rows as $row) {

            $grouped[$row['disease_id']][] = [

                'id' => (int) $row['id'],

                'symptom_en' => $row['symptom_en']

            ];

        }

        return $grouped;
    }
}