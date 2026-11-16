<?php ob_start(); ?>
<section class="section"><div class="wrap narrow prose">
<h1>Terms of Service</h1>
<p>By using WorldTalk you agree to these terms and our Community Guidelines.</p>
<ul>
<li>Public chat is visible to other participants and may be moderated.</li>
<li>Harassment, hate, scams, illegal content, and spam are prohibited.</li>
<li>We may mute, ban, or remove content that violates these terms.</li>
<li>Accounts may be suspended for abuse of the platform.</li>
<li>The service is provided as-is without uptime guarantees.</li>
</ul>
<p>Questions? <a href="/contact">Contact us</a>.</p>
</div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
