<?php
header('Content-Type: application/json');
try {
    require dirname(__DIR__) . '/src/database.php';
    database()->query('SELECT 1 FROM clubs LIMIT 1');
    echo json_encode(['status' => 'ok', 'service' => 'ClubSphere']);
} catch (Throwable $error) {
    error_log('Health check: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'unavailable']);
}
