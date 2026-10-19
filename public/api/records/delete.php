<?php

declare(strict_types=1);

$rootPath = realpath(__DIR__ . '/../../../');
require_once $rootPath . '/src/database/connection.php';
require_once $rootPath . '/src/database/records.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    header('Allow: DELETE');
    echo json_encode(['error' => 'Only DELETE requests are allowed.']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'A positive integer id query parameter is required.']);
    exit;
}

try {
    $connection = Connection::create($rootPath);
    $deleted = (new Records($connection))->delete($id);

    if (!$deleted) {
        http_response_code(404);
        echo json_encode(['error' => 'Record not found.']);
        exit;
    }

    echo json_encode(['message' => 'Record deleted successfully.', 'data' => ['id' => $id]]);
} catch (Throwable $exception) {
    error_log('Record deletion failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to delete record.']);
}
