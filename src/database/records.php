<?php

declare(strict_types=1);

use PDO;

final class Records
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

    public function all(): array
    {
        $statement = $this->connection->query('SELECT id FROM records ORDER BY id ASC');
        return $statement->fetchAll();
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM records WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->rowCount() > 0;
    }
}
