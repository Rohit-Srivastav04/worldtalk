<?php ob_start(); ?>
<section class="section"><div class="wrap narrow prose">
<h1>About WorldTalk</h1>
<p>WorldTalk is a global real-time public chat platform. The idea is simple: one room, open to anyone, where people can meet without ceremony.</p>
<p>We built WorldTalk for instant conversation — guests can join with a temporary name, registered members can keep a profile, and moderators keep the room healthy.</p>
<p>Our mission is respectful, low-friction connection across borders. The product is designed to grow into topic rooms and private messaging without abandoning the original public square.</p>
</div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
