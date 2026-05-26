<?php
/**
 * includes/admin-sidebar.php
 * Shared sidebar for all admin-facing pages.
 *
 * Expects $_SESSION['admin'] to be set (Phase 4 will enforce this).
 * For now name/initial fall back to placeholders.
 *
 * Usage:
 *   require_once __DIR__ . '/../../includes/admin-sidebar.php';
 */

$base         = '/rhu-appointment-system';
$adminName    = $_SESSION['admin']['full_name'] ?? 'Admin User';
$adminInitial = strtoupper($adminName[0] ?? 'A');
$currentPage  = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa-solid fa-hospital"></i></div>
    <div class="brand-text">
      <h3>RHU Rizal</h3>
      <span>Admin Panel</span>
    </div>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar"><?= $adminInitial ?></div>
    <div class="user-info">
      <div class="name"><?= htmlspecialchars($adminName) ?></div>
      <div class="role">System Administrator</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/dashboard.php">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <div class="nav-section-label">Appointments</div>
    <a class="nav-item <?= $currentPage === 'appointments.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/appointments.php">
      <i class="fa-solid fa-calendar-days"></i> Manage Appointments
      <span class="nav-badge" id="pendingBadge"></span>
    </a>
    <a class="nav-item <?= $currentPage === 'calendar.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/calendar.php">
      <i class="fa-solid fa-calendar"></i> View Calendar
    </a>

    <div class="nav-section-label">Doctors &amp; Patients</div>
    <a class="nav-item <?= $currentPage === 'doctors.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/doctors.php">
      <i class="fa-solid fa-user-doctor"></i> Doctor Schedule
    </a>
    <a class="nav-item <?= $currentPage === 'patients.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/patients.php">
      <i class="fa-solid fa-hospital-user"></i> Patient Records
    </a>
    <a class="nav-item <?= $currentPage === 'users.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/users.php">
      <i class="fa-solid fa-users"></i> Manage Users
    </a>

    <div class="nav-section-label">Reports</div>
    <a class="nav-item <?= $currentPage === 'reports.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/reports.php">
      <i class="fa-solid fa-chart-bar"></i> Reports
    </a>

    <div class="nav-section-label">Account</div>
    <a class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>"
       href="<?= $base ?>/views/admin/profile.php">
      <i class="fa-solid fa-circle-user"></i> Admin Profile
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
