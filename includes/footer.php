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

  <script>
    // Force a server round-trip when the browser restores a page from
    // bfcache (Back-Forward Cache). Without this, clicking Back after
    // logout returns the cached dashboard snapshot bypassing session checks.
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) {
        window.location.reload();
      }
    });
  </script>
</body>
</html>
