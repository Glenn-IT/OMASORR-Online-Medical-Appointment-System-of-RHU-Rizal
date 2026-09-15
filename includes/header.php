<?php
/**
 * includes/header.php
 * Shared HTML <head> block for all pages.
 *
 * Usage:
 *   $pageTitle  = "Page Title";          // required
 *   $extraHead  = "<script ...></script>"; // optional – extra CDN scripts for the page
 *   require_once __DIR__ . '/../includes/header.php';
 */

// Base URL helper – works regardless of how deep the page is
$base = '/rhu-appointment-system';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title><?= htmlspecialchars($pageTitle ?? 'RHU Rizal Appointment System') ?></title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <?= $extraHead ?? '' ?>
</head>
<body>
  <!-- Global RHU Rizal Page Loader -->
  <div id="page-loader" class="page-loader" aria-live="polite">
    <div class="loader-content">
      <div class="loader-pulse-ring"></div>
      <div class="loader-icon-box">
        <i class="fa-solid fa-heart-pulse"></i>
      </div>
      <div class="loader-title">RHU Rizal</div>
      <div class="loader-sub">Online Medical Appointment System</div>
      <div class="loader-bar-wrap">
        <div class="loader-bar"></div>
      </div>
    </div>
  </div>

