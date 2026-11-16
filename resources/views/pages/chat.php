<?php ob_start(); ?>
<div class="chat-app" id="chat-app"
  data-ws="<?= e($wsUrl) ?>"
  data-csrf="<?= e($csrf) ?>"
  data-me='<?= e(json_encode($me, JSON_UNESCAPED_UNICODE)) ?>'>
  <header class="chat-top">
    <a class="brand compact" href="/"><span class="brand-mark"></span> WorldTalk</a>
    <div class="chat-meta">
      <strong>Global Chat</strong>
      <span class="pill" id="online-count">0 online</span>
      <span class="status" id="conn-status">Connecting…</span>
    </div>
    <div class="chat-actions">
      <button type="button" class="icon-btn" id="btn-users" aria-label="Online users">People</button>
      <button type="button" class="icon-btn" id="btn-theme" aria-label="Toggle theme">Theme</button>
      <button type="button" class="icon-btn" id="btn-sound" aria-label="Toggle sound">Sound</button>
      <a class="icon-btn" href="/settings">Settings</a>
    </div>
  </header>

  <div class="chat-body">
    <aside class="presence" id="presence-drawer">
      <h2>Online</h2>
      <ul id="presence-list"></ul>
    </aside>
    <section class="feed-wrap">
      <button type="button" class="load-older" id="load-older">Load older messages</button>
      <div class="feed" id="message-feed" role="log" aria-live="polite"></div>
      <div class="typing" id="typing-line" hidden></div>
      <form class="composer" id="composer">
        <button type="button" id="emoji-btn" class="icon-btn" aria-label="Emoji">☺</button>
        <input id="message-input" maxlength="500" placeholder="Message the world…" autocomplete="off" />
        <button class="btn btn-primary" type="submit">Send</button>
      </form>
      <div id="emoji-panel" class="emoji-panel" hidden>😀 😂 ❤️ 🔥 👋 🌍 ✨ 🎉 🙏 💯 😎 🤝</div>
    </section>
  </div>
</div>
<script src="/assets/js/chat.js" defer></script>
<?php
$content = ob_get_clean();
// Chat uses a lean shell without marketing footer
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title><?= e($title) ?></title>
  <meta name="robots" content="noindex,nofollow" />
  <link rel="stylesheet" href="/assets/css/app.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
</head>
<body class="chat-body-page">
<?= $content ?>
</body>
</html>
