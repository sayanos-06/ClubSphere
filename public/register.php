<?php
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/activities.php';
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
}
verify_token();
$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$student = current_student();
if (!$student) {
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your session expired. Sign in again to continue.']);
        exit;
    }
    redirect('/login.php');
}
$id = (int) ($_POST['activity_id'] ?? 0);
try {
    $message = change_registration($student['id'], $id, (string) ($_POST['action'] ?? ''));
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['message' => $message, 'registered' => $_POST['action'] === 'register']);
        exit;
    }
    flash($message);
} catch (DomainException $error) {
    if ($json) {
        http_response_code(409);
        header('Content-Type: application/json');
        echo json_encode(['error' => $error->getMessage()]);
        exit;
    }
    flash($error->getMessage());
}
redirect('/activity.php?id=' . $id);
