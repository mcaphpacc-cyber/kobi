<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->connection();
    }
}