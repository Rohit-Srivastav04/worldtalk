<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use WorldTalk\Support\Database;
use WorldTalk\Support\RedisClient;
use WorldTalk\Support\Router;
use WorldTalk\Support\Session;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

date_default_timezone_set('UTC');

Session::start();

Database::boot();
RedisClient::boot();

$router = new Router();
require $root . '/routes/web.php';
require $root . '/routes/api.php';

return $router;
