<?php

declare(strict_types=1);

return function (): PDO {
    $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbName = $_ENV['DB_DATABASE'] ?? 'fx-auth';
    $user = $_ENV['DB_USERNAME'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';

    $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=utf8mb4', $driver, $host, $port, $dbName);

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
};
