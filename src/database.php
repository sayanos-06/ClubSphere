<?php

function database()
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }
    $file = dirname(__DIR__) . '/.env';
    if (is_file($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
                [$key, $value] = explode('=', $line, 2);
                if (getenv(trim($key)) === false) {
                    putenv(trim($key) . '=' . trim(trim($value), "\"'"));
                }
            }
        }
    }
    $url = parse_url(getenv('DATABASE_URL') ?: '');
    if (empty($url['host'])) {
        throw new RuntimeException('Database configuration is missing.');
    }
    parse_str($url['query'] ?? '', $options);
    $dsn =
        'pgsql:host=' .
        $url['host'] .
        ';port=' .
        ($url['port'] ?? 5432) .
        ';dbname=' .
        ltrim($url['path'], '/') .
        ';sslmode=' .
        ($options['sslmode'] ?? 'require') .
        ';connect_timeout=8';
    $pdo = new PDO($dsn, rawurldecode($url['user']), rawurldecode($url['pass']), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $pdo->exec("SET TIME ZONE 'Asia/Kolkata'");
    return $pdo;
}
