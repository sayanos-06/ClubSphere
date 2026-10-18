<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Database/connection.php';
require_once dirname(__DIR__, 3) . '/src/Database/records.php';

use ClubSphere\Database\Connection;
use ClubSphere\Database\RecordRepository;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Only GET requests are allowed.']);
    exit;
}

try {
    $connection = Connection::create(dirname(__DIR__, 3));
    $records = (new RecordRepository($connection))->all();
    echo json_encode(['data' => $records]);
} catch (Throwable $exception) {
    error_log('Record listing failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to fetch records.']);
}
