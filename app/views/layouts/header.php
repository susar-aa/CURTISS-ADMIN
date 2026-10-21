<?php
declare(strict_types=1);
/** @var string $pageTitle */
$title = ($pageTitle ?? 'Curtiss Admin') . ' | Curtiss Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#09090b">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Curtiss Admin">
  <title><?= htmlspecialchars($title) ?></title>

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.json">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/public/assets/images/favicon.png">
  <link rel="shortcut icon" href="<?= BASE_URL ?>/public/favicon.ico">
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/public/assets/images/favicon.png">

  <!-- Inter Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Curtiss iOS Design System -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= APP_VERSION ?>">
</head>
<body>
<div class="app-wrapper">
