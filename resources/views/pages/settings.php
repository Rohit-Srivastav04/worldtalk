<?php ob_start(); ?>
<section class="section"><div class="wrap narrow card-panel">
<h1>Settings</h1>
<p class="muted">Theme and sound can also be toggled inside chat.</p>
<button class="btn btn-ghost" id="set-theme" type="button">Toggle theme</button>
<form method="post" action="/api/v1/auth/logout" id="logout-form" class="form" style="margin-top:1rem">
  <button class="btn btn-primary" type="button" id="logout-btn">Log out</button>
</form>
</div></section>
<script>
document.getElementById('set-theme').onclick = () => {
  const cur = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', cur);
  localStorage.setItem('wt_theme', cur);
};
document.getElementById('logout-btn').onclick = async () => {
  await fetch('/api/v1/auth/logout', {method:'POST'});
  location.href='/';
};
</script>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
