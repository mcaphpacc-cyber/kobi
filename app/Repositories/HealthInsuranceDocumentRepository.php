<?php

namespace App\Repositories;

class HealthInsuranceDocumentRepository extends BaseRepository
{
    /**
     * Get all documents linked to a policy.
     */
    public function findByPolicyId(int $policyId): array
    {
        $sql = "
            SELECT
                hid.id,
                hid.policy_id,
                hid.document_id,
                hid.document_label,
                hid.created_at,

                hd.title,
                hd.document_type,
                hd.document_date,
                hd.hospital,
                hd.doctor,
                hd.original_filename,
                hd.stored_filename,
                hd.storage_path,
                hd.mime_type,
                hd.file_size

            FROM health_insurance_documents hid

            INNER JOIN health_documents hd
                ON hd.id = hid.document_id

            WHERE hid.policy_id = :policy_id

            ORDER BY
                hd.document_date IS NULL ASC,
                hd.document_date DESC,
                hid.id DESC
        ";

        return $this->fetchAll($sql, [
            ':policy_id' => $policyId,
        ]);
    }

    /**
     * Find an insurance document link by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                policy_id,
                document_id,
                document_label,
                created_at
            FROM health_insurance_documents
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch($sql, [
            ':id' => $id,
        ]) ?: null;
    }

    /**
     * Check whether a document is already linked to a policy.
     */
    public function linkExists(
        int $policyId,
        int $documentId
    ): bool{
        $sql = "
            SELECT 1
            FROM health_insurance_documents
            WHERE policy_id = :policy_id
              AND document_id = :document_id
            LIMIT 1
        ";

        return (bool) $this->fetch($sql, [
            ':policy_id' => $policyId,
            ':document_id' => $documentId,
        ]);
    }

    /**
     * Link a document to an insurance policy.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO health_insurance_documents (
                policy_id,
                document_id,
                document_label
            ) VALUES (
                :policy_id,
                :document_id,
                :document_label
            )
        ";

        $this->query($sql, [
            ':policy_id' => $data['policy_id'],
            ':document_id' => $data['document_id'],
            ':document_label' => $data['document_label'] ?? null,
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Update the label of a linked document.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE health_insurance_documents
            SET
                document_label = :document_label
            WHERE id = :id
        ";

        return $this->execute($sql, [
            ':id' => $id,
            ':document_label' => $data['document_label'] ?? null,
        ]);
    }

    /**
     * Remove a document link from an insurance policy.
     *
     * This does not delete the original health document.
     */
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM health_insurance_documents
            WHERE id = :id
        ";

        return $this->execute($sql, [
            ':id' => $id,
        ]);
    }

    /**
     * Count documents linked to a policy.
     */
    public function countByPolicyId(int $policyId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM health_insurance_documents
            WHERE policy_id = :policy_id
        ";

        $result = $this->fetch($sql, [
            ':policy_id' => $policyId,
        ]);

        return (int) ($result['total'] ?? 0);
    }
}