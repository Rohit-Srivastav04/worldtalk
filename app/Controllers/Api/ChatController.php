<?php

declare(strict_types=1);

namespace WorldTalk\Controllers\Api;

use WorldTalk\Services\AuthService;
use WorldTalk\Services\ChatService;
use WorldTalk\Services\GuestService;
use WorldTalk\Support\Database;

final class ChatController
{
    public function globalRoom(): void
    {
        $room = (new ChatService())->globalRoom();
        if (!$room) {
            json_response(['ok' => false, 'error' => 'Room missing'], 404);
        }
        json_response(['ok' => true, 'room' => $room, 'online' => \WorldTalk\Support\RedisClient::onlineCount()]);
    }

    public function messages(): void
    {
        $room = (new ChatService())->globalRoom();
        if (!$room) {
            json_response(['ok' => false, 'error' => 'Room missing'], 404);
        }
        $before = isset($_GET['before']) ? (int) $_GET['before'] : null;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $msgs = (new ChatService())->history((int) $room['id'], $before, $limit);
        json_response(['ok' => true, 'messages' => $msgs]);
    }

    public function send(): void
    {
        $in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
        if (!verify_csrf($in['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            json_response(['ok' => false, 'error' => 'CSRF failed'], 419);
        }
        $auth = new AuthService();
        $guest = new GuestService();
        $user = $auth->currentUser();
        $g = $guest->currentGuest();
        if (!$user && !$g) {
            json_response(['ok' => false, 'error' => 'Join first'], 401);
        }
        $room = (new ChatService())->globalRoom();
        $res = (new ChatService())->send(
            (int) $room['id'],
            $user['id'] ?? null,
            $g['id'] ?? null,
            (string) ($in['content'] ?? ''),
            $in['uuid'] ?? null
        );
        json_response($res, $res['ok'] ? 201 : 422);
    }

    public function report(string $id): void
    {
        $in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
        $user = (new AuthService())->currentUser();
        $guest = (new GuestService())->currentGuest();
        if (!$user && !$guest) {
            json_response(['ok' => false, 'error' => 'Auth required'], 401);
        }
        $reason = trim((string) ($in['reason'] ?? 'abuse'));
        Database::insert(
            'INSERT INTO message_reports (message_id, reporter_user_id, reporter_guest_session_id, reason, description, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [(int) $id, $user['id'] ?? null, $guest['id'] ?? null, $reason, (string) ($in['description'] ?? ''), 'open']
        );
        json_response(['ok' => true]);
    }

    public function delete(string $id): void
    {
        $user = (new AuthService())->currentUser();
        if (!$user) {
            json_response(['ok' => false, 'error' => 'Auth required'], 401);
        }
        $staff = in_array($user['role'], ['admin', 'moderator'], true);
        $res = (new ChatService())->softDelete((int) $id, (int) $user['id'], $staff);
        json_response($res, $res['ok'] ? 200 : 403);
    }

    public function stats(): void
    {
        json_response(['ok' => true, 'stats' => (new ChatService())->stats()]);
    }
}
