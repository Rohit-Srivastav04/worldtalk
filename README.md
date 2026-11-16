# WorldTalk

**One Room. One World. Real Conversations.**

Production-oriented global real-time public chat platform.

## Stack

- PHP 8.3 + Composer
- MySQL 8
- Redis (presence, rate limits, pub/sub)
- Workerman WebSocket server
- Vanilla JS frontend
- Docker Compose for local/prod-like runs

## Quick start (Docker)

```bash
cp .env.example .env
# edit APP_KEY to a long random string
docker compose up --build -d
docker compose exec php php scripts/migrate.php
```

- Web: http://localhost:8080  
- WebSocket: ws://localhost:2346  
- Admin: use `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`, then visit `/admin`

## Quick start (local PHP)

Requirements: PHP 8.3+, MySQL, Redis, Composer.

```bash
composer install
cp .env.example .env
php scripts/migrate.php
php -S 0.0.0.0:8080 -t public
php websocket/server.php start
```

## Features

- Guest join + registered accounts (Argon2id)
- Global public chat room (REST + WebSocket)
- Presence, typing, reconnect, rate limits
- Moderation reports + admin dashboard
- SEO pages, sitemap, robots.txt
- AdSense-ready flags (ads never inside live chat)

## Security notes

- Prepared statements (PDO)
- CSRF on state-changing APIs
- XSS escaping in views/chat render
- Hashed session tokens
- WebSocket ticket HMAC auth
- CSP + security headers

## License

MIT
