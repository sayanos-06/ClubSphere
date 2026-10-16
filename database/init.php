<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Database/Connection.php';

use ClubSphere\Database\Connection;

try {
    $connection = Connection::create(dirname(__DIR__));
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS records (' .
        'id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY' .
        ')'
    );
    echo "Database initialized successfully. Table 'records' is ready with a unique id column." . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, "Database initialization failed: {$exception->getMessage()}" . PHP_EOL);
    exit(1);
}
