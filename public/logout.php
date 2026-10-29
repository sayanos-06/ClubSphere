<?php
require dirname(__DIR__) . '/src/bootstrap.php';
verify_token();
$_SESSION = [];
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
redirect('/login.php');
