<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$host = env('DB_HOST', '127.0.0.1');
$port = (int) env('DB_PORT', 3306);
$db = env('DB_DATABASE', 'worldtalk');
$user = env('DB_USERNAME', 'worldtalk');
$pass = env('DB_PASSWORD', '');

$pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", (string) $user, (string) $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', (string) $db) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `' . str_replace('`', '``', (string) $db) . '`');

$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/001_init.sql');
$pdo->exec($sql);

$email = (string) env('ADMIN_EMAIL', 'admin@worldtalk.local');
$password = (string) env('ADMIN_PASSWORD', 'ChangeThisAdminPass1!');
$exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$exists->execute([$email]);
if (!$exists->fetch()) {
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, status, email_verified_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute(['admin', $email, $hash, 'admin', 'active']);
    echo "Admin created: {$email}\n";
} else {
    echo "Admin already exists\n";
}

echo "Migration complete.\n";
