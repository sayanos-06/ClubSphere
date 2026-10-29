<?php

date_default_timezone_set('Asia/Kolkata');
ini_set('session.use_strict_mode', '1');
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Cache-Control: no-store');
require_once __DIR__ . '/database.php';

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path, true, 303);
    exit;
}

function token()
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="token" value="' . escape($_SESSION['token']) . '">';
}

function verify_token()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['token']) || !is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Your session expired. Refresh the page and sign in again.']);
            exit;
        }
        exit('This request expired. Return to the previous page, refresh and try again.');
    }
}

function current_student()
{
    static $student = false;
    if ($student !== false) {
        return $student;
    }
    if (empty($_SESSION['student_id'])) {
        return null;
    }
    if (time() - ($_SESSION['active_at'] ?? 0) > 3600) {
        $_SESSION = [];
        return null;
    }
    $_SESSION['active_at'] = time();
    $statement = database()->prepare('SELECT id, name, admission, email, role, version FROM students WHERE id = ?');
    $statement->execute([$_SESSION['student_id']]);
    $student = $statement->fetch() ?: null;
    if ($student && (int) $student['version'] !== (int) ($_SESSION['version'] ?? 0)) {
        $_SESSION = [];
        $student = null;
    }
    return $student;
}

function require_student($organizer = false)
{
    $student = current_student();
    if (!$student) {
        redirect('/login.php');
    }
    if ($organizer && $student['role'] !== 'organizer') {
        http_response_code(403);
        page_start('Access restricted');
        echo '<div class="empty"><h1>Organizer access required</h1><p>This area is available to authorized club organizers.</p><a class="button" href="/index.php">Browse activities</a></div>';
        page_end();
        exit;
    }
    return $student;
}

function flash($message)
{
    $_SESSION['flash'] = $message;
}

set_exception_handler(function ($error) {
    error_log('ClubSphere: ' . $error->getMessage());
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ClubSphere | Temporarily unavailable</title><link rel="stylesheet" href="/assets/style.css"></head><body><main class="container"><div class="empty"><p class="eyebrow">CLUBSPHERE</p><h1>A short pause.</h1><p>We cannot reach the portal right now. Please try again shortly.</p><a class="button" href="/index.php">Try again</a></div></main></body></html>';
});

require_once __DIR__ . '/layout.php';
