<?php
/**
 * includes/logout-modal.php
 * Reusable Logout Confirmation Message Box / Modal for Patient and Admin Portals.
 */
$isLogoutAdmin = function_exists('isLoggedIn') && isLoggedIn('admin');
$logoutUrl     = ($base ?? (defined('BASE_URL') ? BASE_URL : '/rhu-appointment-system')) . '/actions/logout.php';
$logoutTitle   = $isLogoutAdmin ? 'Admin Logout' : 'Confirm Logout';
$logoutMsg     = $isLogoutAdmin
    ? 'Are you sure you want to log out of the Admin Portal? Any unsaved changes may be lost.'
    : 'Are you sure you want to log out of your account? You will need to sign in again to access your appointments.';
?>
<!-- Logout Confirmation Modal -->
<div class="modal-overlay" id="logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
  <div class="modal-box sm">
    <div class="modal-header">
      <h5 id="logoutModalTitle">
        <i class="fa-solid fa-right-from-bracket" style="color:var(--danger)"></i> <?= htmlspecialchars($logoutTitle) ?>
      </h5>
      <button class="modal-close" data-modal-close="logoutModal" type="button" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 24px 20px;">
      <div style="width:56px;height:56px;border-radius:50%;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 16px;">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
      </div>
      <h4 style="font-size:16px;font-weight:600;color:var(--gray-800);margin-bottom:8px;">Ready to leave?</h4>
      <p style="font-size:13.5px;color:var(--gray-500);line-height:1.5;margin:0;">
        <?= htmlspecialchars($logoutMsg) ?>
      </p>
    </div>
    <div class="modal-footer" style="justify-content:center;gap:12px;padding:16px 24px;">
      <button type="button" class="btn btn-secondary" data-modal-close="logoutModal" style="min-width:100px;">
        Cancel
      </button>
      <a href="<?= htmlspecialchars($logoutUrl) ?>" class="btn btn-danger" style="min-width:100px;display:inline-flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </div>
  </div>
</div>
