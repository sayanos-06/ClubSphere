<?php

declare(strict_types=1);

namespace ClubSphere\Database;

use PDO;
use RuntimeException;

final class Connection
{
    public static function create(string $projectRoot): PDO
    {
        self::loadEnv($projectRoot . DIRECTORY_SEPARATOR . '.env');

        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl !== false && $databaseUrl !== '') {
            return new PDO($databaseUrl, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '5432';
        $database = getenv('DB_DATABASE') ?: 'clubsphere';
        $username = getenv('DB_USERNAME') ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: '';

        return new PDO("pgsql:host={$host};port={$port};dbname={$database}", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private static function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException("Environment file not found: {$path}");
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $value = trim(trim($value), "\"'");
            if (getenv(trim($key)) === false) putenv(trim($key) . "={$value}");
        }
    }
}
