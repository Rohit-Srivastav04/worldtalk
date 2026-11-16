<?php

declare(strict_types=1);

namespace WorldTalk\Support;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void
    {
        if (self::$pdo) {
            return;
        }
        $driver = (string) env('DB_CONNECTION', 'mysql');
        try {
            if ($driver === 'sqlite') {
                $path = (string) env('DB_DATABASE', base_path('storage/worldtalk.sqlite'));
                self::$pdo = new PDO('sqlite:' . $path, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
                return;
            }
            $host = (string) env('DB_HOST', '127.0.0.1');
            $port = (int) env('DB_PORT', 3306);
            $db = (string) env('DB_DATABASE', 'worldtalk');
            $user = (string) env('DB_USERNAME', 'worldtalk');
            $pass = (string) env('DB_PASSWORD', '');
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if ((bool) env('APP_DEBUG', false)) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
            throw new RuntimeException('Database unavailable', 0, $e);
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            self::boot();
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::pdo()->lastInsertId();
    }
}
