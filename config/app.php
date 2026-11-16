<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'WorldTalk'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8080'), '/'),
    'key' => (string) env('APP_KEY', 'change-me'),
    'ws_public_url' => (string) env('WS_PUBLIC_URL', 'ws://localhost:2346'),
    'ws_allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('WS_ALLOWED_ORIGINS', '')))),
    'max_message_length' => (int) env('MAX_MESSAGE_LENGTH', 500),
    'rate_limit_messages' => (int) env('RATE_LIMIT_MESSAGES_PER_MIN', 20),
    'adsense_enabled' => (bool) env('ADSENSE_ENABLED', false),
    'adsense_client' => (string) env('ADSENSE_CLIENT_ID', ''),
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'worldtalk'),
        'username' => env('DB_USERNAME', 'worldtalk'),
        'password' => env('DB_PASSWORD', ''),
    ],
    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD') ?: null,
        'prefix' => env('REDIS_PREFIX', 'worldtalk:'),
    ],
];
