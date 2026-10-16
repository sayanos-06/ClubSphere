<?php

declare(strict_types=1);

namespace ClubSphere\Database;

use PDO;

final class RecordRepository
{
    public function __construct(private readonly PDO $connection) {}

    public function create(): int
    {
        return (int) $this->connection
            ->query('INSERT INTO records DEFAULT VALUES RETURNING id')
            ->fetchColumn();
    }
}
