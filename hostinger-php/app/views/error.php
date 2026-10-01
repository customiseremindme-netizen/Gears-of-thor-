<?php
defined('GOT_APP') || exit;
/** @var string $title */
/** @var string $message */
/** @var ?string $detail */
?><!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?></title>
<style>
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0B0B0B; color: #F5F3ED; font: 17px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; padding: 24px; }
  main { max-width: 520px; }
  h1 { font-size: 2rem; line-height: 1.1; margin: 0 0 .75rem; text-transform: uppercase; letter-spacing: .02em; }
  p { color: #B5B5B5; margin: 0 0 1.25rem; }
  a { display: inline-block; background: #D8E12A; color: #0B0B0B; padding: .8rem 1.4rem; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: .08em; font-size: .85rem; }
  code { display: block; background: #171717; color: #F5F3ED; padding: 1rem; font-size: .8rem; margin-bottom: 1.25rem; white-space: pre-wrap; }
</style>
</head>
<body>
<main>
  <h1><?= e($title) ?></h1>
  <p><?= e($message) ?></p>
  <?php if (!empty($detail)): ?><code><?= e($detail) ?></code><?php endif; ?>
  <a href="<?= e(url('/')) ?>">Back to the website</a>
</main>
</body>
</html>
