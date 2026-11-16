<?php

declare(strict_types=1);

use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;
use WorldTalk\Services\AuthService;
use WorldTalk\Services\ChatService;
use WorldTalk\Support\Database;
use WorldTalk\Support\RedisClient;

require dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

Database::boot();
RedisClient::boot();

$wsHost = (string) env('WS_HOST', '0.0.0.0');
$wsPort = (int) env('WS_PORT', 2346);
$worker = new Worker("websocket://{$wsHost}:{$wsPort}");
$worker->count = 1;

/** @var array<int, array{conn:TcpConnection, identity:array, room?:string}> $clients */
$clients = [];
$typing = [];

function send_json(TcpConnection $conn, array $payload): void
{
    $conn->send(json_encode($payload, JSON_UNESCAPED_UNICODE));
}

function broadcast_room(array &$clients, string $room, array $payload, ?int $exceptId = null): void
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    foreach ($clients as $id => $c) {
        if (($c['room'] ?? null) !== $room) {
            continue;
        }
        if ($exceptId !== null && $id === $exceptId) {
            continue;
        }
        $c['conn']->send($json);
    }
}

function presence_snapshot(array $clients, string $room): array
{
    $users = [];
    foreach ($clients as $c) {
        if (($c['room'] ?? null) !== $room) {
            continue;
        }
        $users[] = [
            'name' => $c['identity']['name'] ?? 'User',
            'type' => $c['identity']['type'] ?? 'guest',
        ];
    }
    return $users;
}

$worker->onConnect = function (TcpConnection $conn) {
    send_json($conn, ['event' => 'connected']);
};

$worker->onMessage = function (TcpConnection $conn, $data) use (&$clients, &$typing) {
    $msg = json_decode((string) $data, true);
    if (!is_array($msg) || empty($msg['event'])) {
        send_json($conn, ['event' => 'error', 'error' => 'Invalid payload']);
        return;
    }
    $event = $msg['event'];
    $id = $conn->id;

    if ($event === 'ping') {
        send_json($conn, ['event' => 'pong', 't' => time()]);
        return;
    }

    if ($event === 'auth') {
        $identity = AuthService::verifyWsTicket((string) ($msg['ticket'] ?? ''));
        if (!$identity) {
            send_json($conn, ['event' => 'auth_error', 'error' => 'Invalid ticket']);
            return;
        }
        $clients[$id] = ['conn' => $conn, 'identity' => $identity];
        $key = ($identity['type'] ?? 'guest') . ':' . ($identity['uid'] ?? $identity['gid'] ?? $id);
        RedisClient::setPresence($key, ['name' => $identity['name'] ?? 'User', 'at' => time()]);
        send_json($conn, ['event' => 'auth_success', 'identity' => [
            'name' => $identity['name'] ?? 'User',
            'type' => $identity['type'] ?? 'guest',
            'role' => $identity['role'] ?? 'guest',
        ]]);
        return;
    }

    if (!isset($clients[$id])) {
        send_json($conn, ['event' => 'auth_error', 'error' => 'Authenticate first']);
        return;
    }

    if ($event === 'join_room') {
        $clients[$id]['room'] = 'global';
        $users = presence_snapshot($clients, 'global');
        broadcast_room($clients, 'global', ['event' => 'presence_sync', 'users' => $users]);
        broadcast_room($clients, 'global', ['event' => 'online_count', 'count' => count($users)]);
        broadcast_room($clients, 'global', [
            'event' => 'user_joined',
            'user' => ['name' => $clients[$id]['identity']['name'] ?? 'User'],
        ], $id);
        send_json($conn, ['event' => 'room_joined', 'room' => 'global']);
        return;
    }

    if ($event === 'typing_start' || $event === 'typing_stop') {
        $name = $clients[$id]['identity']['name'] ?? 'User';
        if ($event === 'typing_start') {
            $typing[$id] = $name;
        } else {
            unset($typing[$id]);
        }
        broadcast_room($clients, 'global', [
            'event' => 'typing_update',
            'users' => array_values(array_unique(array_values($typing))),
        ]);
        return;
    }

    if ($event === 'send_message') {
        $identity = $clients[$id]['identity'];
        $chat = new ChatService();
        $room = $chat->globalRoom();
        if (!$room) {
            send_json($conn, ['event' => 'error', 'error' => 'Room missing']);
            return;
        }
        $res = $chat->send(
            (int) $room['id'],
            isset($identity['uid']) ? (int) $identity['uid'] : null,
            isset($identity['gid']) ? (int) $identity['gid'] : null,
            (string) ($msg['content'] ?? ''),
            isset($msg['uuid']) ? (string) $msg['uuid'] : null
        );
        if (!$res['ok']) {
            send_json($conn, ['event' => 'error', 'error' => $res['error'] ?? 'Send failed']);
            return;
        }
        broadcast_room($clients, 'global', ['event' => 'new_message', 'message' => $res['message']]);
        send_json($conn, ['event' => 'message_ack', 'uuid' => $res['message']['uuid'], 'id' => $res['message']['id']]);
        return;
    }
};

$worker->onClose = function (TcpConnection $conn) use (&$clients, &$typing) {
    $id = $conn->id;
    if (!isset($clients[$id])) {
        return;
    }
    $identity = $clients[$id]['identity'];
    $key = ($identity['type'] ?? 'guest') . ':' . ($identity['uid'] ?? $identity['gid'] ?? $id);
    RedisClient::removePresence($key);
    unset($clients[$id], $typing[$id]);
    $users = presence_snapshot($clients, 'global');
    broadcast_room($clients, 'global', ['event' => 'presence_sync', 'users' => $users]);
    broadcast_room($clients, 'global', ['event' => 'online_count', 'count' => count($users)]);
};

// Optional Redis pub/sub fanout for multi-instance later
Timer::add(30, function () use (&$clients) {
    foreach ($clients as $id => $c) {
        $identity = $c['identity'];
        $key = ($identity['type'] ?? 'guest') . ':' . ($identity['uid'] ?? $identity['gid'] ?? $id);
        RedisClient::setPresence($key, ['name' => $identity['name'] ?? 'User', 'at' => time()]);
    }
});

Worker::runAll();
