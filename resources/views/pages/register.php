<?php ob_start(); ?>
<section class="section"><div class="wrap narrow card-panel">
<h1>Create account</h1>
<form id="reg-form" class="form">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />
  <label>Username <input name="username" required maxlength="24" /></label>
  <label>Email <input type="email" name="email" required /></label>
  <label>Password <input type="password" name="password" required minlength="8" /></label>
  <label>Confirm password <input type="password" name="password2" required minlength="8" /></label>
  <label class="check"><input type="checkbox" name="terms" required /> I accept the <a href="/terms">Terms</a></label>
  <button class="btn btn-primary" type="submit">Register</button>
  <p class="error" id="reg-error" hidden></p>
</form>
</div></section>
<script>
document.getElementById('reg-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const err = document.getElementById('reg-error');
  if (fd.get('password') !== fd.get('password2')) { err.textContent='Passwords do not match'; err.hidden=false; return; }
  const res = await fetch('/api/v1/auth/register', {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':fd.get('csrf')},
    body: JSON.stringify({username:fd.get('username'), email:fd.get('email'), password:fd.get('password'), csrf:fd.get('csrf')})
  }).then(r=>r.json());
  if (!res.ok) { err.textContent = res.error; err.hidden=false; return; }
  location.href='/chat';
});
</script>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
