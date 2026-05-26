<?php
/**
 * includes/user-sidebar.php
 * Shared sidebar for all patient-facing pages.
 *
 * Expects $_SESSION['user'] to be set (Phase 4 will enforce this).
 * For now the name/initial fall back to placeholders so the UI
 * looks identical to the original prototype.
 *
 * Usage:
 *   require_once __DIR__ . '/../../includes/user-sidebar.php';
 */

$base        = '/rhu-appointment-system';
$userName    = $_SESSION['user']['full_name'] ?? 'Patient User';
$userInitial = strtoupper($userName[0] ?? 'P');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa-solid fa-hospital"></i></div>
    <div class="brand-text">
      <h3>RHU Rizal</h3>
      <span>Appointment System</span>
    </div>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar"><?= $userInitial ?></div>
    <div class="user-info">
      <div class="name"><?= htmlspecialchars($userName) ?></div>
      <div class="role">Patient</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/user/dashboard.php">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <a class="nav-item <?= $currentPage === 'book-appointment.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/user/book-appointment.php">
      <i class="fa-solid fa-calendar-plus"></i> Book Appointment
    </a>
    <a class="nav-item <?= $currentPage === 'my-appointments.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/user/my-appointments.php">
      <i class="fa-solid fa-calendar-check"></i> My Appointments
    </a>
    <a class="nav-item <?= $currentPage === 'medical-history.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/user/medical-history.php">
      <i class="fa-solid fa-file-medical"></i> Medical History
    </a>

    <div class="nav-section-label">Account</div>
    <a class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/user/profile.php">
      <i class="fa-solid fa-circle-user"></i> My Profile
    </a>
    <a class="nav-item" href="<?= $base ?>/actions/logout.php">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div style="padding:12px 20px;font-size:11px;color:rgba(255,255,255,0.3);line-height:1.5;">
      RHU Rizal &copy; 2026<br />Version 2.0
    </div>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
