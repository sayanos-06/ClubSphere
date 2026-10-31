<?php

require dirname(__DIR__) . '/src/database.php';
$statement = database()->prepare('SELECT content, mime, hash FROM posters WHERE activity_id = ?');
$statement->execute([(int) ($_GET['id'] ?? 0)]);
$poster = $statement->fetch();
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
if (!$poster) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'This activity does not have a poster.';
    exit();
}
header('Content-Type: ' . $poster['mime']);
header('Cache-Control: public, max-age=0, must-revalidate');
header('ETag: "' . $poster['hash'] . '"');
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === '"' . $poster['hash'] . '"') {
    http_response_code(304);
    exit();
}
$content = base64_decode($poster['content'], true);
header('Content-Length: ' . strlen($content));
echo $content;
