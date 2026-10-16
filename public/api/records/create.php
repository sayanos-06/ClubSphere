<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Database/Connection.php';
require_once dirname(__DIR__, 3) . '/src/Database/RecordRepository.php';

use ClubSphere\Database\Connection;
use ClubSphere\Database\RecordRepository;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

try {
    $connection = Connection::create(dirname(__DIR__, 3));
    $recordId = (new RecordRepository($connection))->create();
    http_response_code(201);
    echo json_encode(['message' => 'Record created successfully.', 'data' => ['id' => $recordId]]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to create record.']);
}
