<?php ob_start(); ?>
<section class="section"><div class="wrap">
<h1>Admin dashboard</h1>
<p class="muted">Signed in as <?= e($me['username']) ?> (<?= e($me['role']) ?>)</p>
<div class="cards three">
  <article><h3>Online</h3><p><?= (int)$stats['online'] ?></p></article>
  <article><h3>Users</h3><p><?= (int)$stats['registered_users'] ?></p></article>
  <article><h3>Messages today</h3><p><?= (int)$stats['messages_today'] ?></p></article>
</div>
<h2>Open message reports</h2>
<table class="table">
  <thead><tr><th>ID</th><th>Reason</th><th>Content</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= e($r['reason']) ?></td>
      <td><?= e(mb_strimwidth($r['message_content'], 0, 80, '…')) ?></td>
      <td><?= e($r['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$reports): ?><tr><td colspan="4">No open reports.</td></tr><?php endif; ?>
  </tbody>
</table>
</div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
