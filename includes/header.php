<?php
/**
 * Common Header Component
 * PulTech Social Media Application
 */

require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? APP_NAME ?></title>
  <meta name="description"
    content="<?= $pageDescription ?? 'SISMED - Platform sosial media modern untuk berbagi momen berharga' ?>">
  <meta name="base-url" content="<?= BASE_URL ?>">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">

  <!-- Custom Styles -->
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">

  <?php if (isset($extraCss)): ?>
    <?php foreach ($extraCss as $css): ?>
      <link href="<?= $css ?>" rel="stylesheet">
    <?php endforeach; ?>
  <?php endif; ?>
</head>

<body>
  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>