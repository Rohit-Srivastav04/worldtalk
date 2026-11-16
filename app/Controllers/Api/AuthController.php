<?php

declare(strict_types=1);

namespace WorldTalk\Controllers\Api;

use WorldTalk\Services\AuthService;
use WorldTalk\Services\GuestService;
use WorldTalk\Support\Database;

final class AuthController
{
    public function register(): void
    {
        $in = $this->json();
        if (!verify_csrf($in['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            json_response(['ok' => false, 'error' => 'CSRF failed'], 419);
        }
        $res = (new AuthService())->register(
            (string) ($in['username'] ?? ''),
            (string) ($in['email'] ?? ''),
            (string) ($in['password'] ?? '')
        );
        json_response($res, $res['ok'] ? 201 : 422);
    }

    public function login(): void
    {
        $in = $this->json();
        if (!verify_csrf($in['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            json_response(['ok' => false, 'error' => 'CSRF failed'], 419);
        }
        $res = (new AuthService())->login((string) ($in['email'] ?? ''), (string) ($in['password'] ?? ''));
        json_response($res, $res['ok'] ? 200 : 401);
    }

    public function logout(): void
    {
        (new AuthService())->logout();
        json_response(['ok' => true]);
    }

    public function me(): void
    {
        $user = (new AuthService())->currentUser();
        $guest = (new GuestService())->currentGuest();
        json_response(['ok' => true, 'user' => $user, 'guest' => $guest]);
    }

    public function guestJoin(): void
    {
        $in = $this->json();
        if (!verify_csrf($in['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            json_response(['ok' => false, 'error' => 'CSRF failed'], 419);
        }
        $res = (new GuestService())->join($in['username'] ?? null);
        json_response($res, $res['ok'] ? 201 : 422);
    }

    public function guestRefresh(): void
    {
        json_response((new GuestService())->refresh());
    }

    public function wsTicket(): void
    {
        $ticket = (new AuthService())->issueWsTicket();
        if (!$ticket) {
            json_response(['ok' => false, 'error' => 'Not authenticated'], 401);
        }
        json_response(['ok' => true] + $ticket);
    }

    public function contact(): void
    {
        $in = $this->json();
        if (!verify_csrf($in['csrf'] ?? null)) {
            json_response(['ok' => false, 'error' => 'CSRF failed'], 419);
        }
        $name = trim((string) ($in['name'] ?? ''));
        $email = trim((string) ($in['email'] ?? ''));
        $subject = trim((string) ($in['subject'] ?? ''));
        $body = trim((string) ($in['body'] ?? ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || strlen($body) < 10) {
            json_response(['ok' => false, 'error' => 'Please fill all fields correctly'], 422);
        }
        Database::insert(
            'INSERT INTO contact_messages (name, email, subject, body) VALUES (?, ?, ?, ?)',
            [$name, $email, $subject, $body]
        );
        json_response(['ok' => true, 'message' => 'Message received']);
    }

    private function json(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
        return $_POST;
    }
}
