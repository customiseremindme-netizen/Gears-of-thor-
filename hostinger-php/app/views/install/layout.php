<?php
defined('GOT_APP') || exit;
/** @var string $content */
/** @var string $title */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · GOT FITNEZZ</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin auth-page">
<main class="auth auth--wide">
  <div class="auth__brand">
    <img src="<?= e(asset('img/logo-480.png')) ?>" alt="GOT FITNEZZ — Gears Of Thor logo" width="160" height="117">
  </div>
  <div class="auth__card">
    <?= $content ?>
  </div>
</main>
</body>
</html>
