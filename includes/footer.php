<?php
/**
 * includes/footer.php
 * Shared closing scripts block for all pages.
 *
 * Usage:
 *   $extraScripts = "<script>...</script>"; // optional – inline page scripts
 *   require_once __DIR__ . '/../includes/footer.php';
 */

$base = '/rhu-appointment-system';
?>
  <div id="toast-container"></div>

  <script src="<?= $base ?>/assets/js/app.js"></script>
  <?= $extraScripts ?? '' ?>
</body>
</html>
