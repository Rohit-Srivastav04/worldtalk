<?php

declare(strict_types=1);

namespace WorldTalk\Controllers;

use WorldTalk\Services\AuthService;
use WorldTalk\Services\ChatService;
use WorldTalk\Services\GuestService;
use WorldTalk\Support\Database;

final class PageController
{
    public function home(): void
    {
        $stats = (new ChatService())->stats();
        view('pages.home', [
            'title' => 'WorldTalk — Meet The World. One Message At A Time.',
            'description' => 'Join a real-time global public chat room. Talk with people worldwide instantly — no complicated signup required.',
            'canonical' => config('url') . '/',
            'stats' => $stats,
            'adsense' => config('adsense_enabled'),
        ]);
    }

    public function chat(): void
    {
        $auth = new AuthService();
        $guest = new GuestService();
        $user = $auth->currentUser();
        $g = $guest->currentGuest();
        if (!$user && !$g) {
            header('Location: /join');
            exit;
        }
        view('pages.chat', [
            'title' => 'Global Chat — WorldTalk',
            'description' => 'Live global chat room on WorldTalk.',
            'canonical' => config('url') . '/chat',
            'noindex' => true,
            'me' => $user ?: $g,
            'wsUrl' => config('ws_public_url'),
            'csrf' => csrf_token(),
        ]);
    }

    public function join(): void
    {
        view('pages.join', [
            'title' => 'Join Global Chat — WorldTalk',
            'description' => 'Pick a temporary name and enter the WorldTalk global room instantly.',
            'canonical' => config('url') . '/join',
            'csrf' => csrf_token(),
        ]);
    }

    public function login(): void
    {
        view('pages.login', [
            'title' => 'Log in — WorldTalk',
            'description' => 'Log in to your WorldTalk account.',
            'canonical' => config('url') . '/login',
            'csrf' => csrf_token(),
        ]);
    }

    public function register(): void
    {
        view('pages.register', [
            'title' => 'Create account — WorldTalk',
            'description' => 'Register for WorldTalk to save your profile and join the global conversation.',
            'canonical' => config('url') . '/register',
            'csrf' => csrf_token(),
        ]);
    }

    public function about(): void
    {
        view('pages.about', [
            'title' => 'About WorldTalk',
            'description' => 'WorldTalk is a global real-time public chat platform built for instant human connection.',
            'canonical' => config('url') . '/about',
        ]);
    }

    public function contact(): void
    {
        view('pages.contact', [
            'title' => 'Contact WorldTalk',
            'description' => 'Contact the WorldTalk team for support, appeals, or partnership questions.',
            'canonical' => config('url') . '/contact',
            'csrf' => csrf_token(),
        ]);
    }

    public function privacy(): void
    {
        view('pages.privacy', [
            'title' => 'Privacy Policy — WorldTalk',
            'description' => 'How WorldTalk collects, stores, and protects guest sessions, accounts, and chat data.',
            'canonical' => config('url') . '/privacy',
        ]);
    }

    public function terms(): void
    {
        view('pages.terms', [
            'title' => 'Terms of Service — WorldTalk',
            'description' => 'Terms and acceptable use for the WorldTalk global chat community.',
            'canonical' => config('url') . '/terms',
        ]);
    }

    public function guidelines(): void
    {
        view('pages.guidelines', [
            'title' => 'Community Guidelines — WorldTalk',
            'description' => 'Rules for respectful conversation and reporting on WorldTalk.',
            'canonical' => config('url') . '/guidelines',
        ]);
    }

    public function faq(): void
    {
        view('pages.faq', [
            'title' => 'FAQ — WorldTalk',
            'description' => 'Answers about joining WorldTalk, privacy, safety, accounts, and messaging.',
            'canonical' => config('url') . '/faq',
        ]);
    }

    public function profile(): void
    {
        $user = (new AuthService())->currentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }
        view('pages.profile', [
            'title' => 'Your profile — WorldTalk',
            'noindex' => true,
            'me' => $user,
            'csrf' => csrf_token(),
        ]);
    }

    public function settings(): void
    {
        $user = (new AuthService())->currentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }
        view('pages.settings', [
            'title' => 'Settings — WorldTalk',
            'noindex' => true,
            'me' => $user,
            'csrf' => csrf_token(),
        ]);
    }

    public function admin(): void
    {
        $user = (new AuthService())->currentUser();
        if (!$user || !in_array($user['role'], ['admin', 'moderator'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        $stats = (new ChatService())->stats();
        $reports = Database::fetchAll(
            "SELECT r.*, m.message_content FROM message_reports r
             JOIN messages m ON m.id = r.message_id
             WHERE r.status = 'open' ORDER BY r.id DESC LIMIT 50"
        );
        view('admin.dashboard', [
            'title' => 'Admin — WorldTalk',
            'noindex' => true,
            'me' => $user,
            'stats' => $stats,
            'reports' => $reports,
            'csrf' => csrf_token(),
        ]);
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $url = config('url');
        echo "User-agent: *\nAllow: /\nDisallow: /chat\nDisallow: /api/\nDisallow: /admin\nDisallow: /settings\nDisallow: /profile\nSitemap: {$url}/sitemap.xml\n";
        exit;
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $base = config('url');
        $pages = ['/', '/about', '/contact', '/privacy', '/terms', '/guidelines', '/faq', '/join', '/login', '/register'];
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($pages as $p) {
            echo '<url><loc>' . e($base . $p) . '</loc><changefreq>weekly</changefreq></url>';
        }
        echo '</urlset>';
        exit;
    }
}
