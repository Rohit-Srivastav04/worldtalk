<?php ob_start(); ?>
<section class="section"><div class="wrap narrow card-panel">
<h1>Contact</h1>
<p class="muted">Support, appeals, partnerships. We read every message.</p>
<form id="contact-form" class="form">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />
  <label>Name <input name="name" required /></label>
  <label>Email <input type="email" name="email" required /></label>
  <label>Subject <input name="subject" required /></label>
  <label>Message <textarea name="body" rows="5" required minlength="10"></textarea></label>
  <button class="btn btn-primary" type="submit">Send</button>
  <p class="ok" id="contact-ok" hidden>Thanks — we received your message.</p>
  <p class="error" id="contact-error" hidden></p>
</form>
</div></section>
<script>
document.getElementById('contact-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch('/api/v1/contact', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(Object.fromEntries(fd))}).then(r=>r.json());
  document.getElementById('contact-ok').hidden = !res.ok;
  document.getElementById('contact-error').hidden = !!res.ok;
  if (!res.ok) document.getElementById('contact-error').textContent = res.error || 'Failed';
});
</script>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
