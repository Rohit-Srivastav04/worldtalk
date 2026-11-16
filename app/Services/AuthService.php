<?php

declare(strict_types=1);

namespace WorldTalk\Services;

use WorldTalk\Support\Database;
use WorldTalk\Support\Session;

final class AuthService
{
    public function register(string $username, string $email, string $password): array
    {
        $username = trim($username);
        $email = strtolower(trim($email));
        if (!preg_match('/^[a-zA-Z0-9_]{3,24}$/', $username)) {
            return ['ok' => false, 'error' => 'Username must be 3–24 letters, numbers, or _'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters'];
        }
        $exists = Database::fetch('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1', [$email, $username]);
        if ($exists) {
            return ['ok' => false, 'error' => 'Email or username already taken'];
        }
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $id = Database::insert(
            'INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)',
            [$username, $email, $hash, 'user', 'active']
        );
        $token = bin2hex(random_bytes(32));
        Database::insert(
            'INSERT INTO user_sessions (user_id, session_token_hash, expires_at) VALUES (?, ?, ' . sql_now_plus_days(14) . ')',
            [$id, hash_token($token)]
        );
        Session::regenerate();
        $_SESSION['user_id'] = (int) $id;
        $_SESSION['auth_token'] = $token;
        return ['ok' => true, 'user' => $this->publicUser((int) $id)];
    }

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $ip = ip_hash();
        $recent = Database::fetch(
            'SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND succeeded = 0 AND created_at > ' . sql_minutes_ago(15),
            [$email]
        );
        if ($recent && (int) $recent['c'] >= 8) {
            return ['ok' => false, 'error' => 'Too many login attempts. Try again later.'];
        }
        $user = Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $ok = $user && password_verify($password, $user['password_hash']);
        Database::insert(
            'INSERT INTO login_attempts (email, ip_hash, succeeded) VALUES (?, ?, ?)',
            [$email, $ip, $ok ? 1 : 0]
        );
        if (!$ok) {
            return ['ok' => false, 'error' => 'Invalid credentials'];
        }
        if ($user['status'] !== 'active') {
            return ['ok' => false, 'error' => 'Account is not active'];
        }
        $token = bin2hex(random_bytes(32));
        Database::insert(
            'INSERT INTO user_sessions (user_id, session_token_hash, expires_at) VALUES (?, ?, ' . sql_now_plus_days(14) . ')',
            [$user['id'], hash_token($token)]
        );
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['auth_token'] = $token;
        unset($_SESSION['guest_id'], $_SESSION['guest_token']);
        return ['ok' => true, 'user' => $this->publicUser((int) $user['id'])];
    }

    public function logout(): void
    {
        if (!empty($_SESSION['auth_token'])) {
            Database::query('DELETE FROM user_sessions WHERE session_token_hash = ?', [hash_token($_SESSION['auth_token'])]);
        }
        unset($_SESSION['user_id'], $_SESSION['auth_token']);
    }

    public function currentUser(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        return $this->publicUser((int) $id);
    }

    public function publicUser(int $id): ?array
    {
        $u = Database::fetch(
            'SELECT id, username, email, avatar, role, status, created_at, email_verified_at FROM users WHERE id = ?',
            [$id]
        );
        if (!$u || $u['status'] === 'banned') {
            return null;
        }
        return [
            'id' => (int) $u['id'],
            'username' => $u['username'],
            'email' => $u['email'],
            'avatar' => $u['avatar'],
            'role' => $u['role'],
            'status' => $u['status'],
            'created_at' => $u['created_at'],
            'email_verified' => !empty($u['email_verified_at']),
            'type' => 'user',
        ];
    }

    public function issueWsTicket(): ?array
    {
        $user = $this->currentUser();
        $guest = (new GuestService())->currentGuest();
        if (!$user && !$guest) {
            return null;
        }
        $payload = [
            'exp' => time() + 120,
            'uid' => $user['id'] ?? null,
            'gid' => $guest['id'] ?? null,
            'name' => $user['username'] ?? $guest['username'],
            'type' => $user ? 'user' : 'guest',
            'role' => $user['role'] ?? 'guest',
            'nonce' => bin2hex(random_bytes(8)),
        ];
        $body = base64_encode(json_encode($payload));
        $sig = hash_hmac('sha256', $body, (string) env('APP_KEY', 'key'));
        return ['ticket' => $body . '.' . $sig, 'identity' => $payload];
    }

    public static function verifyWsTicket(string $ticket): ?array
    {
        $parts = explode('.', $ticket, 2);
        if (count($parts) !== 2) {
            return null;
        }
        [$body, $sig] = $parts;
        $expect = hash_hmac('sha256', $body, (string) env('APP_KEY', 'key'));
        if (!hash_equals($expect, $sig)) {
            return null;
        }
        $payload = json_decode(base64_decode($body, true) ?: '', true);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }
        return $payload;
    }
}
