<?php
define('CURRENT_VERSION', 'v1.05');
$base = '/rhu-appointment-system';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Under Construction – RHU Rizal</title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body { margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f0f4f8; }
    .uc-card {
      text-align: center;
      background: #fff;
      border-radius: 16px;
      padding: 56px 48px;
      box-shadow: 0 4px 24px rgba(0,0,0,.10);
      max-width: 420px;
      width: 90%;
    }
    .uc-icon { font-size: 56px; color: #f59e0b; margin-bottom: 20px; }
    .uc-title { font-size: 26px; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
    .uc-version { display: inline-block; background: #eff6ff; color: #2563eb; font-size: 13px; font-weight: 600; padding: 4px 14px; border-radius: 999px; margin-bottom: 16px; }
    .uc-text { color: #64748b; font-size: 15px; margin: 0 0 28px; line-height: 1.6; }
    .uc-back { display: inline-block; padding: 10px 28px; background: #2563eb; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background .2s; }
    .uc-back:hover { background: #1d4ed8; }
  </style>
</head>
<body>
  <div class="uc-card">
    <div class="uc-icon"><i class="fas fa-hard-hat"></i></div>
    <div class="uc-version">Current Version: <?= CURRENT_VERSION ?></div>
    <h1 class="uc-title">Under Construction</h1>
    <p class="uc-text">This page is not yet available in the current version.<br>It will be unlocked in a future release.</p>
    <a href="javascript:history.back()" class="uc-back"><i class="fas fa-arrow-left"></i> Go Back</a>
  </div>
</body>
</html>
<?php exit; ?>
