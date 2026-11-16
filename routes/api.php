<?php

declare(strict_types=1);

use WorldTalk\Controllers\Api\AuthController;
use WorldTalk\Controllers\Api\ChatController;

/** @var \WorldTalk\Support\Router $router */

$router->post('/api/v1/auth/register', [AuthController::class, 'register']);
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/v1/users/me', [AuthController::class, 'me']);
$router->post('/api/v1/guest/join', [AuthController::class, 'guestJoin']);
$router->post('/api/v1/guest/refresh', [AuthController::class, 'guestRefresh']);
$router->get('/api/v1/ws/ticket', [AuthController::class, 'wsTicket']);
$router->post('/api/v1/contact', [AuthController::class, 'contact']);

$router->get('/api/v1/rooms/global', [ChatController::class, 'globalRoom']);
$router->get('/api/v1/rooms/global/messages', [ChatController::class, 'messages']);
$router->post('/api/v1/messages', [ChatController::class, 'send']);
$router->post('/api/v1/messages/{id}/report', [ChatController::class, 'report']);
$router->delete('/api/v1/messages/{id}', [ChatController::class, 'delete']);
$router->get('/api/v1/stats', [ChatController::class, 'stats']);
