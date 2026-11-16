<?php ob_start(); ?>
<section class="section"><div class="wrap narrow prose">
<h1>FAQ</h1>
<details open><summary>How do I join?</summary><p>Open <a href="/join">Join</a>, pick a name, and enter Global Chat.</p></details>
<details><summary>Is registration required?</summary><p>No for basic chat. Register to keep a stable profile.</p></details>
<details><summary>Are messages private?</summary><p>Global Chat is public. Don’t share secrets.</p></details>
<details><summary>How do I report abuse?</summary><p>Use the report action on a message. Moderators review reports.</p></details>
<details><summary>Do you show my IP?</summary><p>No. Abuse systems may store hashed IPs server-side only.</p></details>
</div></section>
<?php $content = ob_get_clean(); require base_path('resources/views/layouts/main.php'); ?>
