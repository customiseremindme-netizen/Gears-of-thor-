<?php
defined('GOT_APP') || exit;
/** @var bool $existing */
?>
<h1>Your website is ready</h1>
<p><?= $existing
    ? 'The website is connected to your existing database. Your content and enquiries were kept, and your login has been set.'
    : 'The database is set up, your logo is loaded and the website is live with the starting content.' ?></p>
<ol class="steps">
  <li>Sign in to the dashboard with the email and password you just chose.</li>
  <li>Open the <strong>Owner checklist</strong> to confirm your opening hours and other details.</li>
  <li>Upload your own gym photos in <strong>Photos & videos</strong>.</li>
</ol>
<p><a class="btn btn--primary btn--block" href="<?= e(url('/admin/login')) ?>">Go to the dashboard</a></p>
<p class="small muted"><a href="<?= e(url('/')) ?>">View the website</a></p>
