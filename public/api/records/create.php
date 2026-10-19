<?php

declare(strict_types=1);

$rootPath = realpath(__DIR__ . '/../../../');
require_once $rootPath . '/src/database/connection.php';
require_once $rootPath . '/src/database/records.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

try {
    $connection = Connection::create($rootPath);
    $recordId = (new Records($connection))->create();
    http_response_code(201);
    echo json_encode(['message' => 'Record created successfully.', 'data' => ['id' => $recordId]]);
} catch (Throwable $exception) {
    error_log('Record creation failed: ' . $exception->getMessage());
    http_response_code(500);
    $response = ['error' => 'Unable to create record.'];
    if (getenv('APP_DEBUG') === 'true') {
        $response['details'] = $exception->getMessage();
    }
    echo json_encode($response);
}
