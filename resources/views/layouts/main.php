<?php
/** @var string $title */
/** @var string|null $description */
/** @var string|null $canonical */
/** @var bool|null $noindex */
$description = $description ?? 'WorldTalk — global real-time public chat.';
$canonical = $canonical ?? config('url');
$noindex = $noindex ?? false;
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>" />
  <link rel="canonical" href="<?= e($canonical) ?>" />
  <?php if ($noindex): ?><meta name="robots" content="noindex,nofollow" /><?php endif; ?>
  <meta property="og:title" content="<?= e($title) ?>" />
  <meta property="og:description" content="<?= e($description) ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="<?= e($canonical) ?>" />
  <meta name="theme-color" content="#0B1020" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/css/app.css" />
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => 'WorldTalk',
    'url' => config('url'),
    'applicationCategory' => 'CommunicationApplication',
    'description' => $description,
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
</head>
<body>
  <?php require base_path('resources/views/components/nav.php'); ?>
  <main>
    <?= $content ?? '' ?>
  </main>
  <?php require base_path('resources/views/components/footer.php'); ?>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
