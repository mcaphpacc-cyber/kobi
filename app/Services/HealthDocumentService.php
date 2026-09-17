<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HealthDocumentRepository;
use RuntimeException;

class HealthDocumentService
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    private const ALLOWED_DOCUMENT_TYPES = [
        'lab_report',
        'imaging',
        'prescription',
        'discharge_summary',
        'medical_record',
        'surgery_procedure',
        'consultation',
        'other',
    ];

    public function __construct(
        private HealthDocumentRepository $documentRepository,
        private HealthProfileService $healthProfileService,
        private HealthDocumentStorageService $storageService,
        private AuthService $authService
    ) {
    }

    /**
     * Get all documents accessible to the authenticated user.
     */
    public function getDocuments(int $profileId): array
    {
        $this->requireAuthenticatedUser();

        $this->healthProfileService->getProfile($profileId);

        return $this->documentRepository->findByProfileId(
            $profileId
        );
    }

    /**
     * Get the number of documents accessible to the authenticated user.
     */
    public function getDocumentCount(int $profileId): int
    {
        $this->requireAuthenticatedUser();

        $this->healthProfileService->getProfile($profileId);

        return $this->documentRepository->countByProfileId(
            $profileId
        );
    }

    /**
     * Get a single document after verifying profile access.
     */
    public function getDocument(int $id): array
    {
        $this->requireAuthenticatedUser();

        $document =
            $this->documentRepository->findById($id);

        if ($document === null) {
            throw new RuntimeException(
                'Medical document not found.'
            );
        }

        $this->healthProfileService->getProfile(
            (int) $document['health_profile_id']
        );

        return $document;
    }

    /**
     * Create a medical document.
     */
    public function createDocument(
        int $profileId,
        array $file,
        array $data
    ): int {
        $userId =
            $this->requireAuthenticatedUser();

        $this->requireEditableProfile(
            $profileId,
            $userId
        );

        $validatedFile =
            $this->validateUploadedFile($file);

        $title =
            $this->normalizeRequiredText(
                $data['title'] ?? '',
                'Document title'
            );

        $documentType =
            $this->normalizeDocumentType(
                $data['document_type'] ?? ''
            );

        $documentDate =
            $this->normalizeDate(
                $data['document_date'] ?? null
            );

        $hospital =
            $this->normalizeOptionalText(
                $data['hospital'] ?? null,
                255
            );

        $doctor =
            $this->normalizeOptionalText(
                $data['doctor'] ?? null,
                255
            );

        $notes =
            $this->normalizeOptionalText(
                $data['notes'] ?? null
            );

        $storedFile = null;

        try
        {
            $storedFile =
                $this->storageService->store(
                    $profileId,
                    $validatedFile['tmp_name'],
                    $validatedFile['extension']
                );

            return $this->documentRepository->create([
                'health_profile_id' =>
                    $profileId,

                'uploaded_by' =>
                    $userId,

                'title' =>
                    $title,

                'document_type' =>
                    $documentType,

                'document_date' =>
                    $documentDate,

                'hospital' =>
                    $hospital,

                'doctor' =>
                    $doctor,

                'notes' =>
                    $notes,

                'original_filename' =>
                    $validatedFile['original_filename'],

                'stored_filename' =>
                    $storedFile['stored_filename'],

                'storage_path' =>
                    $storedFile['storage_path'],

                'mime_type' =>
                    $validatedFile['mime_type'],

                'file_size' =>
                    $validatedFile['file_size'],
            ]);
        }
        catch (\Throwable $exception)
        {
            if (
                is_array($storedFile)
                &&
                !empty($storedFile['storage_path'])
            ) {
                try
                {
                    $this->storageService->delete(
                        $storedFile['storage_path']
                    );
                }
                catch (\Throwable)
                {
                    // Do not hide the original exception.
                }
            }

            throw $exception;
        }
    }

    /**
     * Update document metadata.
     *
     * File replacement is intentionally not supported in v1.
     */
    public function updateDocument(
        int $id,
        array $data
    ): bool {
        $userId =
            $this->requireAuthenticatedUser();

        $document =
            $this->documentRepository->findById($id);

        if ($document === null) {
            throw new RuntimeException(
                'Medical document not found.'
            );
        }

        $this->requireEditableProfile(
            (int) $document['health_profile_id'],
            $userId
        );

        $title =
            $this->normalizeRequiredText(
                $data['title'] ?? '',
                'Document title'
            );

        $documentType =
            $this->normalizeDocumentType(
                $data['document_type'] ?? ''
            );

        $documentDate =
            $this->normalizeDate(
                $data['document_date'] ?? null
            );

        $hospital =
            $this->normalizeOptionalText(
                $data['hospital'] ?? null,
                255
            );

        $doctor =
            $this->normalizeOptionalText(
                $data['doctor'] ?? null,
                255
            );

        $notes =
            $this->normalizeOptionalText(
                $data['notes'] ?? null
            );

        return $this->documentRepository->update(
            $id,
            [
                'title' =>
                    $title,

                'document_type' =>
                    $documentType,

                'document_date' =>
                    $documentDate,

                'hospital' =>
                    $hospital,

                'doctor' =>
                    $doctor,

                'notes' =>
                    $notes,
            ]
        );
    }

    /**
     * Delete a document.
     *
     * Delete permission is owner-only.
     */
    public function deleteDocument(int $id): bool
    {
        $userId =
            $this->requireAuthenticatedUser();

        $document =
            $this->documentRepository->findById($id);

        if ($document === null) {
            throw new RuntimeException(
                'Medical document not found.'
            );
        }

        $profile =
            $this->healthProfileService->getProfile(
                (int) $document['health_profile_id']
            );

        if (
            ($profile['role'] ?? null) !== 'owner'
        ) {
            throw new RuntimeException(
                'You do not have permission to delete this medical document.'
            );
        }

        /*
         * Remove the physical file first.
         *
         * If it is already missing, continue with metadata
         * deletion. This keeps deletion idempotent for a
         * previously damaged/missing file.
         */
        if (
            !empty($document['storage_path'])
        ) {
            $this->storageService->delete(
                $document['storage_path']
            );
        }

        return $this->documentRepository->delete(
            $id
        );
    }

    /**
     * Get the absolute filesystem path for an accessible document.
     */
    public function getFilePath(int $id): string
    {
        $document =
            $this->getDocument($id);

        $storagePath =
            trim(
                (string) (
                    $document['storage_path'] ?? ''
                )
            );

        if ($storagePath === '') {
            throw new RuntimeException(
                'Medical document storage path is unavailable.'
            );
        }

        if (
            !$this->storageService->exists(
                $storagePath
            )
        ) {
            throw new RuntimeException(
                'Medical document file could not be found.'
            );
        }

        return $this->storageService->getPath(
            $storagePath
        );
    }

    /**
     * Return the current authenticated user ID.
     */
    private function requireAuthenticatedUser(): int
    {
        if (!$this->authService->check())
        {
            throw new RuntimeException(
                'Authentication required.'
            );
        }

        $userId =
            $this->authService->id();

        if ($userId === null)
        {
            throw new RuntimeException(
                'Authenticated user could not be determined.'
            );
        }

        return $userId;
    }

    /**
     * Require owner/editor access.
     */
    private function requireEditableProfile(
        int $profileId,
        int $userId
    ): void {
        $profile =
            $this->healthProfileService->getProfile(
                $profileId
            );

        $role =
            $profile['role'] ?? null;

        if (
            $role !== 'owner'
            &&
            $role !== 'editor'
        ) {
            throw new RuntimeException(
                'You do not have permission to modify this health record.'
            );
        }
    }

    /**
     * Validate the uploaded file using PHP upload information
     * and server-side MIME detection.
     */
    private function validateUploadedFile(
        array $file
    ): array {
        $error =
            (int) (
                $file['error']
                ?? UPLOAD_ERR_NO_FILE
            );

        if ($error !== UPLOAD_ERR_OK)
        {
            throw new RuntimeException(
                $this->getUploadErrorMessage($error)
            );
        }

        $tmpName =
            trim(
                (string) (
                    $file['tmp_name']
                    ?? ''
                )
            );

        if (
            $tmpName === ''
            ||
            !is_uploaded_file($tmpName)
        ) {
            throw new RuntimeException(
                'Invalid uploaded medical document.'
            );
        }

        $fileSize =
            filesize($tmpName);

        if ($fileSize === false)
        {
            throw new RuntimeException(
                'Medical document size could not be determined.'
            );
        }

        if ($fileSize <= 0)
        {
            throw new RuntimeException(
                'Medical document cannot be empty.'
            );
        }

        if ($fileSize > self::MAX_FILE_SIZE)
        {
            throw new RuntimeException(
                'Medical document exceeds the maximum allowed size of 20 MB.'
            );
        }

        $finfo =
            new \finfo(
                FILEINFO_MIME_TYPE
            );

        $mimeType =
            $finfo->file($tmpName);

        if (
            $mimeType === false
            ||
            !isset(
                self::ALLOWED_MIME_TYPES[$mimeType]
            )
        ) {
            throw new RuntimeException(
                'Unsupported medical document format.'
            );
        }

        $extension =
            self::ALLOWED_MIME_TYPES[$mimeType];

        $originalFilename =
            trim(
                (string) (
                    $file['name']
                    ?? ''
                )
            );

        if ($originalFilename === '')
        {
            $originalFilename =
                'medical-document.' .
                $extension;
        }

        $originalFilename =
            $this->sanitizeOriginalFilename(
                $originalFilename
            );

        return [
            'tmp_name' =>
                $tmpName,

            'original_filename' =>
                $originalFilename,

            'mime_type' =>
                $mimeType,

            'file_size' =>
                $fileSize,

            'extension' =>
                $extension,
        ];
    }

    private function normalizeDocumentType(
        mixed $value
    ): string {
        $value =
            trim(
                (string) (
                    $value ?? ''
                )
            );

        if (
            !in_array(
                $value,
                self::ALLOWED_DOCUMENT_TYPES,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid medical document type.'
            );
        }

        return $value;
    }

    private function normalizeDate(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) (
                    $value ?? ''
                )
            );

        if ($value === '')
        {
            return null;
        }

        $date =
            \DateTime::createFromFormat(
                'Y-m-d',
                $value
            );

        if (
            !$date
            ||
            $date->format('Y-m-d') !== $value
        ) {
            throw new RuntimeException(
                'Invalid document date.'
            );
        }

        $today =
            new \DateTime('today');

        if ($date > $today)
        {
            throw new RuntimeException(
                'Document dates cannot be in the future.'
            );
        }

        return $value;
    }

    private function normalizeRequiredText(
        mixed $value,
        string $fieldName,
        int $maxLength = 255
    ): string {
        $value =
            trim(
                (string) (
                    $value ?? ''
                )
            );

        if ($value === '')
        {
            throw new RuntimeException(
                $fieldName . ' is required.'
            );
        }

        if (
            mb_strlen($value) > $maxLength
        ) {
            throw new RuntimeException(
                $fieldName .
                ' exceeds the maximum allowed length.'
            );
        }

        return $value;
    }

    private function normalizeOptionalText(
        mixed $value,
        ?int $maxLength = null
    ): ?string {
        $value =
            trim(
                (string) (
                    $value ?? ''
                )
            );

        if ($value === '')
        {
            return null;
        }

        if (
            $maxLength !== null
            &&
            mb_strlen($value) > $maxLength
        ) {
            throw new RuntimeException(
                'Text exceeds the maximum allowed length.'
            );
        }

        return $value;
    }

    private function sanitizeOriginalFilename(
        string $filename
    ): string {
        $filename =
            basename(
                str_replace(
                    '\\',
                    '/',
                    $filename
                )
            );

        $filename =
            preg_replace(
                '/[\x00-\x1F\x7F]/u',
                '',
                $filename
            ) ?? '';

        $filename =
            trim($filename);

        if ($filename === '')
        {
            return 'medical-document';
        }

        if (
            mb_strlen($filename) > 255
        ) {
            $filename =
                mb_substr(
                    $filename,
                    0,
                    255
                );
        }

        return $filename;
    }

    private function getUploadErrorMessage(
        int $error
    ): string {
        return match ($error)
        {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE =>
                'Medical document exceeds the maximum allowed upload size.',

            UPLOAD_ERR_PARTIAL =>
                'Medical document upload was incomplete.',

            UPLOAD_ERR_NO_FILE =>
                'Please select a medical document to upload.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'Medical document upload failed because the temporary directory is unavailable.',

            UPLOAD_ERR_CANT_WRITE =>
                'Medical document could not be written to temporary storage.',

            UPLOAD_ERR_EXTENSION =>
                'Medical document upload was blocked by the server.',

            default =>
                'Medical document upload failed.',
        };
    }
}