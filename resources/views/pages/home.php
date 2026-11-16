<?php ob_start(); ?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <p class="eyebrow">Global real-time chat</p>
      <h1>Meet The World.<br />One Message At A Time.</h1>
      <p class="lede">Join one shared public room and talk with people online right now — guests welcome, no complicated signup.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="/join">Join Global Chat</a>
        <a class="btn btn-ghost" href="/about">How it works</a>
      </div>
      <dl class="stat-strip" aria-label="Live community stats">
        <div><dt>Online now</dt><dd id="stat-online"><?= (int) $stats['online'] ?></dd></div>
        <div><dt>Messages today</dt><dd><?= (int) $stats['messages_today'] ?></dd></div>
        <div><dt>Registered</dt><dd><?= (int) $stats['registered_users'] ?></dd></div>
      </dl>
    </div>
    <div class="hero-panel" aria-hidden="true">
      <div class="fake-chat">
        <div class="bubble"><strong>Aya</strong><span>anyone else up?</span></div>
        <div class="bubble"><strong>Marco</strong><span>hello from Lisbon 👋</span></div>
        <div class="bubble"><strong>Guest4821</strong><span>first time here — cool room</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="how">
  <div class="wrap">
    <h2>How It Works</h2>
    <p class="section-sub">Three steps. Zero friction.</p>
    <div class="cards three">
      <article><h3>1. Pick a name</h3><p>Join as a guest with a temporary username, or create an account.</p></article>
      <article><h3>2. Enter Global Chat</h3><p>Everyone lands in the same live room by default.</p></article>
      <article><h3>3. Talk in real time</h3><p>Messages stream instantly over WebSockets — no refresh.</p></article>
    </div>
  </div>
</section>

<section class="section alt">
  <div class="wrap">
    <h2>Built for Conversation</h2>
    <div class="cards three">
      <article><h3>Presence</h3><p>See who’s online, typing indicators, and connection status.</p></article>
      <article><h3>Moderation</h3><p>Reports, filters, mutes, and staff tools keep the room usable.</p></article>
      <article><h3>Privacy-minded</h3><p>IPs stay server-side. Messages are escaped. Sessions are hashed.</p></article>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <h2>Safety first</h2>
    <p>Rate limits, spam checks, report flows, and community guidelines protect the room. Read our <a href="/guidelines">Guidelines</a> before you dive in.</p>
  </div>
</section>

<section class="section alt" id="faq-preview">
  <div class="wrap narrow">
    <h2>FAQ</h2>
    <details open><summary>Do I need an account?</summary><p>No. Guests can join instantly. Accounts unlock profiles and settings.</p></details>
    <details><summary>Is the chat public?</summary><p>Yes — Global Chat is one shared public room. Don’t share secrets.</p></details>
    <details><summary>Can I report abuse?</summary><p>Yes. Report messages from the chat UI. Moderators review the queue.</p></details>
    <p><a href="/faq">More answers →</a></p>
  </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
