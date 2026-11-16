<?php

declare(strict_types=1);

namespace WorldTalk\Support;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (bool) env('COOKIE_SECURE', false);
        $sameSite = (string) env('COOKIE_SAMESITE', 'Lax');
        session_name((string) env('SESSION_NAME', 'wt_session'));
        session_set_cookie_params([
            'lifetime' => (int) env('SESSION_LIFETIME', 1209600),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);
        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
