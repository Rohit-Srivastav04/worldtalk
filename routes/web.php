<?php

declare(strict_types=1);

use WorldTalk\Controllers\PageController;

/** @var \WorldTalk\Support\Router $router */

$router->get('/', [PageController::class, 'home']);
$router->get('/chat', [PageController::class, 'chat']);
$router->get('/join', [PageController::class, 'join']);
$router->get('/login', [PageController::class, 'login']);
$router->get('/register', [PageController::class, 'register']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/contact', [PageController::class, 'contact']);
$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/terms', [PageController::class, 'terms']);
$router->get('/guidelines', [PageController::class, 'guidelines']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/profile', [PageController::class, 'profile']);
$router->get('/settings', [PageController::class, 'settings']);
$router->get('/admin', [PageController::class, 'admin']);
$router->get('/robots.txt', [PageController::class, 'robots']);
$router->get('/sitemap.xml', [PageController::class, 'sitemap']);
