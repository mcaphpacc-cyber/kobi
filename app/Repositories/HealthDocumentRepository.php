<?php

declare(strict_types=1);

namespace App\Repositories;

class HealthDocumentRepository extends BaseRepository
{
    /**
     * Find all documents for a health profile.
     */
    public function findByProfileId(
        int $profileId
    ): array {
        $sql = "
            SELECT
                id,
                health_profile_id,
                uploaded_by,
                title,
                document_type,
                document_date,
                hospital,
                doctor,
                notes,
                original_filename,
                stored_filename,
                storage_path,
                mime_type,
                file_size,
                created_at,
                updated_at
            FROM health_documents
            WHERE health_profile_id = :health_profile_id
            ORDER BY
                document_date IS NULL ASC,
                document_date DESC,
                id DESC
        ";

        return $this->fetchAll(
            $sql,
            [
                'health_profile_id' => $profileId
            ]
        );
    }

    /**
     * Count all documents for a health profile.
     */
    public function countByProfileId(
        int $profileId
    ): int {
        $sql = "
            SELECT COUNT(*) AS total
            FROM health_documents
            WHERE health_profile_id = :health_profile_id
        ";

        $row =
            $this->fetch(
                $sql,
                [
                    'health_profile_id' => $profileId
                ]
            );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Find one document by ID.
     */
    public function findById(
        int $id
    ): ?array {
        $sql = "
            SELECT
                id,
                health_profile_id,
                uploaded_by,
                title,
                document_type,
                document_date,
                hospital,
                doctor,
                notes,
                original_filename,
                stored_filename,
                storage_path,
                mime_type,
                file_size,
                created_at,
                updated_at
            FROM health_documents
            WHERE id = :id
            LIMIT 1
        ";

        return $this->fetch(
            $sql,
            [
                'id' => $id
            ]
        );
    }

    /**
     * Create a document record.
     */
    public function create(
        array $data
    ): int {
        $sql = "
            INSERT INTO health_documents (
                health_profile_id,
                uploaded_by,
                title,
                document_type,
                document_date,
                hospital,
                doctor,
                notes,
                original_filename,
                stored_filename,
                storage_path,
                mime_type,
                file_size
            )
            VALUES (
                :health_profile_id,
                :uploaded_by,
                :title,
                :document_type,
                :document_date,
                :hospital,
                :doctor,
                :notes,
                :original_filename,
                :stored_filename,
                :storage_path,
                :mime_type,
                :file_size
            )
        ";

        $this->query(
            $sql,
            [
                'health_profile_id' =>
                    $data['health_profile_id'],

                'uploaded_by' =>
                    $data['uploaded_by'],

                'title' =>
                    $data['title'],

                'document_type' =>
                    $data['document_type'],

                'document_date' =>
                    $data['document_date'] ?? null,

                'hospital' =>
                    $data['hospital'] ?? null,

                'doctor' =>
                    $data['doctor'] ?? null,

                'notes' =>
                    $data['notes'] ?? null,

                'original_filename' =>
                    $data['original_filename'],

                'stored_filename' =>
                    $data['stored_filename'],

                'storage_path' =>
                    $data['storage_path'],

                'mime_type' =>
                    $data['mime_type'],

                'file_size' =>
                    $data['file_size']
            ]
        );

        return (int) $this->lastInsertId();
    }

    /**
     * Update document metadata.
     */
    public function update(
        int $id,
        array $data
    ): bool {
        $sql = "
            UPDATE health_documents
            SET
                title = :title,
                document_type = :document_type,
                document_date = :document_date,
                hospital = :hospital,
                doctor = :doctor,
                notes = :notes
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id' => $id,

                'title' =>
                    $data['title'],

                'document_type' =>
                    $data['document_type'],

                'document_date' =>
                    $data['document_date'] ?? null,

                'hospital' =>
                    $data['hospital'] ?? null,

                'doctor' =>
                    $data['doctor'] ?? null,

                'notes' =>
                    $data['notes'] ?? null
            ]
        );
    }

    /**
     * Delete a document record.
     */
    public function delete(
        int $id
    ): bool {
        $sql = "
            DELETE FROM health_documents
            WHERE id = :id
        ";

        return $this->execute(
            $sql,
            [
                'id' => $id
            ]
        );
    }
}