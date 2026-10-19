<?php

declare(strict_types=1);

$rootPath = realpath(__DIR__ . '/../../../');
require_once $rootPath . '/src/database/connection.php';
require_once $rootPath . '/src/database/records.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Only GET requests are allowed.']);
    exit;
}

try {
    $connection = Connection::create($rootPath);
    $records = (new Records($connection))->all();
    echo json_encode(['data' => $records]);
} catch (Throwable $exception) {
    error_log('Record listing failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to fetch records.']);
}
