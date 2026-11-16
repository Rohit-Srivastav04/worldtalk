<?php ob_start(); ?>
<section class="section"><div class="wrap narrow prose">
<h1>Privacy Policy</h1>
<p>Last updated: <?= date('F j, Y') ?></p>
<p>WorldTalk (“we”) operates a global public chat service. This policy explains what we collect and why.</p>
<h2>Account data</h2>
<p>Registered users provide username, email, and password (stored as Argon2id hashes). Optional avatar and settings are stored with the account.</p>
<h2>Guest sessions</h2>
<p>Guests receive a temporary username and a session token. Tokens are stored hashed. Guest sessions expire automatically.</p>
<h2>Messages</h2>
<p>Public chat messages are stored to display history and support moderation. Deleted messages are soft-deleted. Do not share sensitive personal data in public chat.</p>
<h2>Technical logs</h2>
<p>We may store hashed IP addresses for abuse prevention and login attempt tracking. IP addresses are never shown to other users.</p>
<h2>Cookies</h2>
<p>We use a secure session cookie for authentication. Preferences such as theme may use local storage on your device.</p>
<h2>Advertising</h2>
<p>If advertising is enabled by administrators, it appears only on eligible informational pages — never inside the live chat conversation UI.</p>
<h2>Your rights</h2>
<p>Contact us via the <a href="/contact">Contact</a> page to request account deletion or data questions.</p>
</div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
