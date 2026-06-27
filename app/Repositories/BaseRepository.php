<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOStatement;

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->connection();
    }

    /**
     * Execute a prepared statement.
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);

        $statement->execute($params);

        return $statement;
    }

    /**
     * Fetch a single row.
     */
    protected function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();

        return $result === false ? null : $result;
    }

    /**
     * Fetch multiple rows.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Execute INSERT, UPDATE or DELETE.
     */
    protected function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params)->rowCount() > 0;
    }

    /**
     * Last inserted ID.
     */
    protected function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }

    /**
     * Transaction support.
     */
    protected function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    protected function commit(): bool
    {
        return $this->db->commit();
    }

    protected function rollback(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * Count all records in a table.
     */
    protected function countRecords(string $table): int
    {
        $row = $this->fetch(
            "SELECT COUNT(*) AS total FROM {$table}"
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Check whether a record exists.
     */
    protected function exists(
        string $table,
        string $column,
        mixed $value
    ): bool {

        $row = $this->fetch(
            "SELECT COUNT(*) AS total
            FROM {$table}
            WHERE {$column} = :value",
            [
                'value' => $value
            ]
        );

        return ((int) ($row['total'] ?? 0)) > 0;
    }
}