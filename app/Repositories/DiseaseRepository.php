<?php

declare(strict_types=1);

namespace App\Repositories;
use App\Services\Disease\QuickFactsBuilder;

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

    private function getCausesForDisease(
        int $diseaseId
    ): array
    {
        $sql = "

            SELECT

                c.id,

                c.cause_en,

                c.cause_hi

            FROM disease_causes dc

            INNER JOIN causes c

                ON c.id = dc.cause_id

            WHERE dc.disease_id = :id

            ORDER BY c.cause_en

        ";

        return $this->fetchAll($sql, [

            'id' => $diseaseId

        ]);
    }

    private function getTreatmentsForDisease(
        int $diseaseId
    ): array
    {
        return [];
    }

    private function getFaqsForDisease(
        int $diseaseId
    ): array
    {
        return [];
    }

    public function findKnowledgeBySlug(
        string $slug,
        string $language = 'en'
    ): ?array
    {
        $sql = "
            SELECT

                d.*,

                dc.*

            FROM diseases d

            LEFT JOIN disease_content dc

                ON dc.disease_id = d.id

            WHERE d.slug = :slug

            LIMIT 1
        ";

        $disease = $this->fetch($sql, [

            'slug' => $slug

        ]);

        if (!$disease) {
            return null;
        }

        $id = (int) $disease['id'];

        $content = [

            'overview_en'      => $disease['overview_en'] ?? '',
            'overview_hi'      => $disease['overview_hi'] ?? '',

            'causes_en'        => $disease['causes_en'] ?? '',
            'causes_hi'        => $disease['causes_hi'] ?? '',

            'risk_factors_en'  => $disease['risk_factors_en'] ?? '',
            'risk_factors_hi'  => $disease['risk_factors_hi'] ?? '',

            'diagnosis_en'     => $disease['diagnosis_en'] ?? '',
            'diagnosis_hi'     => $disease['diagnosis_hi'] ?? '',

            'prevention_en'    => $disease['prevention_en'] ?? '',
            'prevention_hi'    => $disease['prevention_hi'] ?? ''

        ];

        return [

            'disease' => $disease,

            'content' => $content,

            'symptoms' => $this->getSymptomsForDisease($id),

            'causes' => $this->getCausesForDisease($id),

            'treatments' => $this->getTreatmentsForDisease($id),

            'faqs' => $this->getFaqsForDisease($id)

        ];
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

    public function getSymptomsForDisease(
        int $diseaseId
    ): array
    {
        $sql = "

            SELECT

                s.id,

                s.symptom_en,

                s.symptom_hi

            FROM disease_symptoms ds

            INNER JOIN symptoms s

                ON s.id = ds.symptom_id

            WHERE ds.disease_id = :id

            ORDER BY s.symptom_en

        ";

        return $this->fetchAll($sql, [

            'id' => $diseaseId,

        ]);
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
            d.urgency_note,
            dc.risk_factors_en,
            dc.causes_en,
            dc.diagnosis_en,
            dc.prevention_en,
            dc.overview_en
        FROM diseases d
        INNER JOIN disease_symptoms ds
            ON ds.disease_id = d.id
        INNER JOIN disease_content dc
            ON dc.disease_id = d.id
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

    $disease['quickFacts'] =
    QuickFactsBuilder::build($disease);

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