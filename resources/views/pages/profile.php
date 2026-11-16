<?php ob_start(); ?>
<section class="section"><div class="wrap narrow card-panel">
<h1>Profile</h1>
<p><strong><?= e($me['username']) ?></strong></p>
<p class="muted">Joined <?= e($me['created_at'] ?? '') ?></p>
<p>Role: <?= e($me['role']) ?></p>
<a class="btn btn-ghost" href="/settings">Edit settings</a>
</div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
