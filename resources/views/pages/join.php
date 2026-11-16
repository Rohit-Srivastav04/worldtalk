<?php ob_start(); ?>
<section class="section">
  <div class="wrap narrow card-panel">
    <h1>Join Global Chat</h1>
    <p class="muted">Choose a temporary name and jump into the room. You can register later.</p>
    <form id="guest-form" class="form">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />
      <label>Display name
        <input name="username" maxlength="24" placeholder="e.g. Nova" autocomplete="nickname" required />
      </label>
      <button class="btn btn-primary" type="submit">Enter room</button>
      <p class="muted tiny">By joining you agree to the <a href="/terms">Terms</a> and <a href="/guidelines">Guidelines</a>.</p>
      <p class="error" id="guest-error" hidden></p>
    </form>
  </div>
</section>
<script>
document.getElementById('guest-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const err = document.getElementById('guest-error');
  err.hidden = true;
  const res = await fetch('/api/v1/guest/join', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN': fd.get('csrf')},
    body: JSON.stringify({username: fd.get('username'), csrf: fd.get('csrf')})
  }).then(r => r.json());
  if (!res.ok) { err.textContent = res.error || 'Failed'; err.hidden = false; return; }
  location.href = '/chat';
});
</script>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
