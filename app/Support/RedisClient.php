<?php

declare(strict_types=1);

namespace WorldTalk\Support;

use Predis\Client;
use Throwable;

final class RedisClient
{
    private static ?Client $client = null;
    private static bool $available = false;

    public static function boot(): void
    {
        if (self::$client) {
            return;
        }
        try {
            $params = [
                'scheme' => 'tcp',
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => (int) env('REDIS_PORT', 6379),
            ];
            $password = env('REDIS_PASSWORD');
            if ($password) {
                $params['password'] = $password;
            }
            self::$client = new Client($params);
            self::$client->ping();
            self::$available = true;
        } catch (Throwable) {
            self::$client = null;
            self::$available = false;
        }
    }

    public static function available(): bool
    {
        return self::$available && self::$client !== null;
    }

    public static function client(): ?Client
    {
        return self::$client;
    }

    public static function key(string $suffix): string
    {
        return (string) env('REDIS_PREFIX', 'worldtalk:') . $suffix;
    }

    public static function incrRate(string $bucket, int $ttlSeconds): int
    {
        if (!self::available()) {
            return 0;
        }
        $key = self::key('rate:' . $bucket);
        $n = (int) self::$client->incr($key);
        if ($n === 1) {
            self::$client->expire($key, $ttlSeconds);
        }
        return $n;
    }

    public static function setPresence(string $identity, array $payload, int $ttl = 45): void
    {
        if (!self::available()) {
            return;
        }
        $key = self::key('presence:' . $identity);
        self::$client->setex($key, $ttl, json_encode($payload));
        self::$client->sadd(self::key('presence:online'), [$identity]);
    }

    public static function removePresence(string $identity): void
    {
        if (!self::available()) {
            return;
        }
        self::$client->del([self::key('presence:' . $identity)]);
        self::$client->srem(self::key('presence:online'), $identity);
    }

    public static function onlineCount(): int
    {
        if (!self::available()) {
            return 0;
        }
        $members = self::$client->smembers(self::key('presence:online')) ?: [];
        $alive = 0;
        foreach ($members as $id) {
            if (self::$client->exists(self::key('presence:' . $id))) {
                $alive++;
            } else {
                self::$client->srem(self::key('presence:online'), $id);
            }
        }
        return $alive;
    }

    public static function publish(string $channel, array $payload): void
    {
        if (!self::available()) {
            return;
        }
        self::$client->publish(self::key($channel), json_encode($payload));
    }
}
