<?php ob_start(); ?>
<section class="section"><div class="wrap narrow"><h1>404</h1><p>Page not found. <a href="/">Go home</a></p></div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
