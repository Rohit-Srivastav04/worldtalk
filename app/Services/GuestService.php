<?php

declare(strict_types=1);

namespace WorldTalk\Services;

use WorldTalk\Support\Database;
use WorldTalk\Support\Session;

final class GuestService
{
    public function join(?string $username = null): array
    {
        $username = trim((string) $username);
        if ($username === '') {
            $username = 'Guest' . random_int(1000, 9999);
        }
        if (!preg_match('/^[a-zA-Z0-9_ ]{2,24}$/', $username)) {
            return ['ok' => false, 'error' => 'Invalid guest name'];
        }
        $username = preg_replace('/\s+/', ' ', $username) ?: 'Guest';
        $token = bin2hex(random_bytes(32));
        $id = Database::insert(
            'INSERT INTO guest_sessions (username, session_token_hash, expires_at, last_active_at, status)
             VALUES (?, ?, ' . sql_now_plus_days(7) . ', ' . sql_now() . ', ?)',
            [$username, hash_token($token), 'active']
        );
        Session::regenerate();
        $_SESSION['guest_id'] = (int) $id;
        $_SESSION['guest_token'] = $token;
        unset($_SESSION['user_id'], $_SESSION['auth_token']);
        return [
            'ok' => true,
            'guest' => [
                'id' => (int) $id,
                'username' => $username,
                'type' => 'guest',
                'token' => $token,
            ],
        ];
    }

    public function currentGuest(): ?array
    {
        $id = $_SESSION['guest_id'] ?? null;
        $token = $_SESSION['guest_token'] ?? null;
        if (!$id || !$token) {
            return null;
        }
        $row = Database::fetch(
            'SELECT id, username, status, expires_at FROM guest_sessions WHERE id = ? AND session_token_hash = ? LIMIT 1',
            [$id, hash_token($token)]
        );
        if (!$row || $row['status'] !== 'active' || strtotime($row['expires_at']) < time()) {
            return null;
        }
        Database::query('UPDATE guest_sessions SET last_active_at = ' . sql_now() . ' WHERE id = ?', [$id]);
        return [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'type' => 'guest',
        ];
    }

    public function refresh(): array
    {
        $g = $this->currentGuest();
        if (!$g) {
            return ['ok' => false, 'error' => 'Guest session expired'];
        }
        Database::query(
            'UPDATE guest_sessions SET expires_at = ' . sql_now_plus_days(7) . ' WHERE id = ?',
            [$g['id']]
        );
        return ['ok' => true, 'guest' => $g];
    }
}
