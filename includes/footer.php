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

  <?php $jsVersion = file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : '1.0'; ?>
  <script src="<?= $base ?>/assets/js/app.js?v=<?= $jsVersion ?>"></script>
  <?= $extraScripts ?? '' ?>

  <script>
    // Force a server round-trip when the browser restores a page from
    // bfcache (Back-Forward Cache) or history navigation.
    window.addEventListener('pageshow', function (e) {
      if (e.persisted || (window.performance && window.performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
        window.location.reload();
      }
    });
  </script>
</body>
</html>
