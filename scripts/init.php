<?php

declare(strict_types=1);

$rootPath = realpath(__DIR__ . '/..');
require_once $rootPath . '/src/database/connection.php';

try {
    $connection = Connection::create($rootPath);
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
