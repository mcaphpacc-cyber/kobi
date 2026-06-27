<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            env('DB_CONNECTION', 'mysql'),
            env('DB_HOST', 'localhost'),
            env('DB_PORT', '3306'),
            env('DB_DATABASE'),
            env('DB_CHARSET', 'utf8mb4')
        );

        try {

            $this->pdo = new PDO(
                $dsn,
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

        } catch (PDOException $e) {

            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage()
            );
        }
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }
}