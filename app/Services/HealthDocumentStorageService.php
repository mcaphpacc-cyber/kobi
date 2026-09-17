<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class HealthDocumentStorageService
{
    private const STORAGE_DIRECTORY = 'health-documents';

    /**
     * @var string
     */
    private string $storageRoot;

    public function __construct()
    {
        $storageRoot =
            trim(
                (string) config('storage_path')
            );

        if ($storageRoot === '') {
            throw new RuntimeException(
                'Medical document storage path is not configured.'
            );
        }

        $this->storageRoot =
            rtrim(
                $storageRoot,
                DIRECTORY_SEPARATOR
            );
    }

    /**
     * Store an uploaded medical document.
     *
     * Returns storage metadata required by the database layer.
     */
    public function store(
        int $profileId,
        string $sourcePath,
        string $extension
    ): array {
        if ($profileId <= 0) {
            throw new RuntimeException(
                'Invalid health profile.'
            );
        }

        if ($sourcePath === '') {
            throw new RuntimeException(
                'Invalid uploaded file.'
            );
        }

        if (!is_file($sourcePath)) {
            throw new RuntimeException(
                'Uploaded file could not be found.'
            );
        }

        $extension =
            strtolower(
                trim($extension)
            );

        $allowedExtensions = [
            'pdf',
            'jpg',
            'jpeg',
            'png',
            'webp',
        ];

        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {
            throw new RuntimeException(
                'Unsupported medical document format.'
            );
        }

        $profileDirectory =
            $this->getProfileDirectory(
                $profileId
            );

        if (
            !is_dir($profileDirectory)
            &&
            !mkdir(
                $profileDirectory,
                0750,
                true
            )
            &&
            !is_dir($profileDirectory)
        ) {
            throw new RuntimeException(
                'Medical document storage directory could not be created.'
            );
        }

        $randomName =
            bin2hex(
                random_bytes(32)
            );

        $storedFilename =
            $randomName .
            '.' .
            $extension;

        $absolutePath =
            $profileDirectory .
            DIRECTORY_SEPARATOR .
            $storedFilename;

        if (
            !move_uploaded_file(
                $sourcePath,
                $absolutePath
            )
        ) {
            throw new RuntimeException(
                'Medical document could not be stored.'
            );
        }

        @chmod(
            $absolutePath,
            0640
        );

        return [
            'stored_filename' => $storedFilename,
            'storage_path' =>
                self::STORAGE_DIRECTORY .
                '/' .
                $profileId .
                '/' .
                $storedFilename,
        ];
    }

    /**
     * Check whether a stored document exists.
     */
    public function exists(
        string $storagePath
    ): bool {
        $absolutePath =
            $this->resolveStoragePath(
                $storagePath
            );

        return is_file($absolutePath);
    }

    /**
     * Resolve a stored document to its absolute
     * filesystem path.
     */
    public function getPath(
        string $storagePath
    ): string {
        return $this->resolveStoragePath(
            $storagePath
        );
    }

    /**
     * Delete a stored document.
     */
    public function delete(
        string $storagePath
    ): bool {
        $absolutePath =
            $this->resolveStoragePath(
                $storagePath
            );

        if (!is_file($absolutePath)) {
            return false;
        }

        return unlink($absolutePath);
    }

    /**
     * Get the profile-specific storage directory.
     */
    private function getProfileDirectory(
        int $profileId
    ): string {
        return
            $this->storageRoot .
            DIRECTORY_SEPARATOR .
            self::STORAGE_DIRECTORY .
            DIRECTORY_SEPARATOR .
            $profileId;
    }

    /**
     * Resolve a relative storage path safely.
     */
    private function resolveStoragePath(
        string $storagePath
    ): string {
        $storagePath =
            trim(
                str_replace(
                    '\\',
                    '/',
                    $storagePath
                ),
                '/'
            );

        if ($storagePath === '') {
            throw new RuntimeException(
                'Invalid medical document storage path.'
            );
        }

        if (
            str_contains(
                $storagePath,
                "\0"
            )
        ) {
            throw new RuntimeException(
                'Invalid medical document storage path.'
            );
        }

        $parts =
            explode(
                '/',
                $storagePath
            );

        if (
            in_array(
                '..',
                $parts,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid medical document storage path.'
            );
        }

        $prefix =
            self::STORAGE_DIRECTORY . '/';

        if (
            !str_starts_with(
                $storagePath,
                $prefix
            )
        ) {
            throw new RuntimeException(
                'Invalid medical document storage path.'
            );
        }

        return
            $this->storageRoot .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $storagePath
            );
    }
}