<?php

declare(strict_types=1);

namespace WorldTalk\Services;

use WorldTalk\Support\Database;
use WorldTalk\Support\RedisClient;

final class ChatService
{
    public function globalRoom(): ?array
    {
        return Database::fetch("SELECT id, name, slug, description, type, status FROM rooms WHERE slug = 'global' LIMIT 1");
    }

    public function history(int $roomId, ?int $beforeId = null, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        if ($beforeId) {
            $rows = Database::fetchAll(
                'SELECT m.*, u.username AS user_name, u.avatar AS user_avatar, g.username AS guest_name
                 FROM messages m
                 LEFT JOIN users u ON u.id = m.sender_user_id
                 LEFT JOIN guest_sessions g ON g.id = m.sender_guest_session_id
                 WHERE m.room_id = ? AND m.deleted_at IS NULL AND m.id < ?
                 ORDER BY m.id DESC LIMIT ' . $limit,
                [$roomId, $beforeId]
            );
        } else {
            $rows = Database::fetchAll(
                'SELECT m.*, u.username AS user_name, u.avatar AS user_avatar, g.username AS guest_name
                 FROM messages m
                 LEFT JOIN users u ON u.id = m.sender_user_id
                 LEFT JOIN guest_sessions g ON g.id = m.sender_guest_session_id
                 WHERE m.room_id = ? AND m.deleted_at IS NULL
                 ORDER BY m.id DESC LIMIT ' . $limit,
                [$roomId]
            );
        }
        $out = array_map([$this, 'formatMessage'], array_reverse($rows));
        return $out;
    }

    public function send(int $roomId, ?int $userId, ?int $guestId, string $content, ?string $uuid = null): array
    {
        $content = trim($content);
        $max = (int) env('MAX_MESSAGE_LENGTH', 500);
        if ($content === '' || mb_strlen($content) > $max) {
            return ['ok' => false, 'error' => 'Invalid message length'];
        }
        if ($this->isProfane($content)) {
            return ['ok' => false, 'error' => 'Message blocked by filter'];
        }
        $allowLinks = Database::fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'allow_links'");
        if (($allowLinks['setting_value'] ?? '0') !== '1' && preg_match('#https?://|www\.#i', $content)) {
            return ['ok' => false, 'error' => 'Links are disabled in Global Chat'];
        }

        $bucket = ($userId ? 'u' . $userId : 'g' . $guestId) . ':msg';
        $count = RedisClient::incrRate($bucket, 60);
        $limit = (int) env('RATE_LIMIT_MESSAGES_PER_MIN', 20);
        if ($count > $limit) {
            return ['ok' => false, 'error' => 'Slow down — rate limit reached'];
        }

        $last = Database::fetch(
            'SELECT message_content FROM messages WHERE room_id = ? AND ((sender_user_id IS NULL AND ? IS NULL) OR sender_user_id = ?) AND ((sender_guest_session_id IS NULL AND ? IS NULL) OR sender_guest_session_id = ?) ORDER BY id DESC LIMIT 1',
            [$roomId, $userId, $userId, $guestId, $guestId]
        );
        if ($last && hash_equals((string) $last['message_content'], $content)) {
            return ['ok' => false, 'error' => 'Duplicate message'];
        }

        $uuid = $uuid && preg_match('/^[a-f0-9\-]{36}$/i', $uuid) ? $uuid : uuid_v4();
        try {
            $id = Database::insert(
                'INSERT INTO messages (room_id, sender_user_id, sender_guest_session_id, message_uuid, message_type, message_content)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$roomId, $userId, $guestId, $uuid, 'text', $content]
            );
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'Could not save message'];
        }

        $row = Database::fetch(
            'SELECT m.*, u.username AS user_name, u.avatar AS user_avatar, g.username AS guest_name
             FROM messages m
             LEFT JOIN users u ON u.id = m.sender_user_id
             LEFT JOIN guest_sessions g ON g.id = m.sender_guest_session_id
             WHERE m.id = ?',
            [$id]
        );
        $msg = $this->formatMessage($row);
        RedisClient::publish('chat:global', ['event' => 'new_message', 'message' => $msg]);
        return ['ok' => true, 'message' => $msg];
    }

    public function softDelete(int $messageId, int $actorId, bool $isStaff): array
    {
        $msg = Database::fetch('SELECT * FROM messages WHERE id = ?', [$messageId]);
        if (!$msg || $msg['deleted_at']) {
            return ['ok' => false, 'error' => 'Message not found'];
        }
        $owns = $msg['sender_user_id'] && (int) $msg['sender_user_id'] === $actorId;
        if (!$owns && !$isStaff) {
            return ['ok' => false, 'error' => 'Forbidden'];
        }
        Database::query('UPDATE messages SET deleted_at = ' . sql_now() . ' WHERE id = ?', [$messageId]);
        RedisClient::publish('chat:global', ['event' => 'message_deleted', 'id' => $messageId]);
        return ['ok' => true];
    }

    public function formatMessage(?array $row): array
    {
        if (!$row) {
            return [];
        }
        $name = $row['user_name'] ?: ($row['guest_name'] ?: 'Unknown');
        return [
            'id' => (int) $row['id'],
            'uuid' => $row['message_uuid'],
            'room_id' => (int) $row['room_id'],
            'content' => $row['message_content'],
            'type' => $row['message_type'],
            'created_at' => $row['created_at'],
            'edited_at' => $row['edited_at'],
            'sender' => [
                'user_id' => $row['sender_user_id'] ? (int) $row['sender_user_id'] : null,
                'guest_id' => $row['sender_guest_session_id'] ? (int) $row['sender_guest_session_id'] : null,
                'username' => $name,
                'avatar' => $row['user_avatar'] ?? null,
                'type' => $row['sender_user_id'] ? 'user' : 'guest',
            ],
        ];
    }

    private function isProfane(string $content): bool
    {
        $enabled = Database::fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'profanity_filter'");
        if (($enabled['setting_value'] ?? '1') !== '1') {
            return false;
        }
        $bad = ['fuck', 'shit', 'bitch', 'asshole', 'nigger', 'faggot'];
        $lower = mb_strtolower($content);
        foreach ($bad as $w) {
            if (str_contains($lower, $w)) {
                return true;
            }
        }
        return false;
    }

    public function stats(): array
    {
        $users = Database::fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0;
        $guests = Database::fetch("SELECT COUNT(*) AS c FROM guest_sessions WHERE status = 'active' AND expires_at > " . sql_now())['c'] ?? 0;
        $messages = Database::fetch('SELECT COUNT(*) AS c FROM messages WHERE deleted_at IS NULL')['c'] ?? 0;
        $today = Database::fetch('SELECT COUNT(*) AS c FROM messages WHERE deleted_at IS NULL AND created_at >= ' . sql_today())['c'] ?? 0;
        return [
            'registered_users' => (int) $users,
            'active_guests' => (int) $guests,
            'messages_total' => (int) $messages,
            'messages_today' => (int) $today,
            'online' => RedisClient::onlineCount(),
        ];
    }
}
