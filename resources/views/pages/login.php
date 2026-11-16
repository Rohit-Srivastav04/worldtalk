<?php ob_start(); ?>
<section class="section"><div class="wrap narrow card-panel">
<h1>Log in</h1>
<form id="login-form" class="form">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />
  <label>Email <input type="email" name="email" required /></label>
  <label>Password <input type="password" name="password" required /></label>
  <label class="check"><input type="checkbox" name="remember" /> Remember me</label>
  <button class="btn btn-primary" type="submit">Log in</button>
  <p class="muted">No account? <a href="/register">Register</a></p>
  <p class="error" id="login-error" hidden></p>
</form>
</div></section>
<script>
document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const err = document.getElementById('login-error');
  const res = await fetch('/api/v1/auth/login', {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':fd.get('csrf')},
    body: JSON.stringify({email:fd.get('email'), password:fd.get('password'), csrf:fd.get('csrf')})
  }).then(r=>r.json());
  if (!res.ok) { err.textContent = res.error; err.hidden=false; return; }
  location.href='/chat';
});
</script>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
