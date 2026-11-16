<?php

declare(strict_types=1);

function db_driver(): string
{
    return (string) env('DB_CONNECTION', 'mysql');
}

function sql_now(): string
{
    return db_driver() === 'sqlite' ? "datetime('now')" : 'NOW()';
}

function sql_now_plus_days(int $days): string
{
    if (db_driver() === 'sqlite') {
        return "datetime('now', '+" . $days . " day')";
    }
    return 'DATE_ADD(NOW(), INTERVAL ' . $days . ' DAY)';
}

function sql_today(): string
{
    return db_driver() === 'sqlite' ? "date('now')" : 'CURDATE()';
}

function sql_minutes_ago(int $minutes): string
{
    if (db_driver() === 'sqlite') {
        return "datetime('now', '-" . $minutes . " minute')";
    }
    return 'DATE_SUB(NOW(), INTERVAL ' . $minutes . ' MINUTE)';
}
