<?php
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/activities.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit();
}
$activities = database()
    ->query(activity_query() . ' ORDER BY a.id')
    ->fetchAll();
$result = [];
foreach ($activities as $activity) {
    $result[] = [
        'id' => (int) $activity['id'],
        'remaining' => max(0, (int) $activity['capacity'] - (int) $activity['registered']),
        'registered' => (int) $activity['registered'],
        'status' => activity_state($activity)
    ];
}
header('Content-Type: application/json');
echo json_encode(['activities' => $result]);
