<?php

declare(strict_types=1);

namespace ClubSphere\Database;

use PDO;

final class RecordRepository
{
    public function __construct(private readonly PDO $connection) {}

    public function create(): int
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS records (' .
            'id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY' .
            ')'
        );

        return (int) $this->connection
            ->query('INSERT INTO records DEFAULT VALUES RETURNING id')
            ->fetchColumn();
    }
}
