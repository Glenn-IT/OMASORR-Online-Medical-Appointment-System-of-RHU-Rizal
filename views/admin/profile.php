<?php require_once __DIR__ . '/../../components/under-construction.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');

$admin     = getAdminSession();
$adminId   = (int) $admin['id'];
$fullName  = htmlspecialchars($admin['full_name'] ?? '');
$email     = htmlspecialchars($admin['email']     ?? '');
$phone     = htmlspecialchars($admin['phone']     ?? '');
$role      = htmlspecialchars($admin['role']      ?? 'System Administrator');
$username  = htmlspecialchars($admin['username']  ?? '');
$initial   = strtoupper(substr($fullName, 0, 1) ?: 'A');

$profileFlash  = getFlash('profile_error')  ?? getFlash('profile_success');
$passwordFlash = getFlash('password_error') ?? getFlash('password_success');

$pageTitle = 'Admin Profile – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/admin-sidebar.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px">
          <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');"><i class="fa-solid fa-bars"></i></button>
          <div><h4>Admin Profile</h4><p>View and manage your account</p></div>
        </div>
        <div class="topbar-right">
          <div class="topbar-user">
            <div class="avatar"><?= $initial ?></div>
            <span class="user-name"><?= $fullName ?></span>
          </div>
        </div>
      </header>

      <div class="page-content">

        <?php if ($profileFlash): ?>
        <div class="alert alert-<?= $profileFlash['type'] ?> mb-3">
          <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($profileFlash['message']) ?>
        </div>
        <?php endif; ?>

        <?php if ($passwordFlash): ?>
        <div class="alert alert-<?= $passwordFlash['type'] ?> mb-3">
          <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($passwordFlash['message']) ?>
        </div>
        <?php endif; ?>

        <div class="grid-2" style="align-items:start">

          <!-- Profile Card -->
          <div class="card">
            <div class="card-body text-center" style="padding:32px">
              <div class="profile-avatar"><?= $initial ?></div>
              <h3 style="font-size:18px;font-weight:700;margin-bottom:4px"><?= $fullName ?></h3>
              <p style="font-size:13px;color:#888;margin-bottom:6px"><?= $email ?: '—' ?></p>
              <div style="display:inline-flex;align-items:center;gap:6px;background:var(--primary-light);padding:5px 14px;border-radius:20px;margin-bottom:24px;">
                <span class="status-dot active"></span>
                <span style="font-size:12px;font-weight:600;color:var(--primary)"><?= $role ?></span>
              </div>
              <div style="display:flex;gap:10px;justify-content:center">
                <button class="btn btn-primary" onclick="openModal('editProfileModal')">
                  <i class="fa-solid fa-pen"></i> Edit Profile
                </button>
                <button class="btn btn-outline-danger" onclick="openModal('changePasswordModal')">
                  <i class="fa-solid fa-key"></i> Change Password
                </button>
              </div>
            </div>
          </div>

          <!-- Account Details Card -->
          <div class="card">
            <div class="card-header"><h5><i class="fa-solid fa-id-card"></i> Account Information</h5></div>
            <div class="card-body">
              <div class="detail-list">
                <div class="detail-item">
                  <div class="detail-label">Username</div>
                  <div class="detail-value fw-600 text-primary"><?= $username ?></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Full Name</div>
                  <div class="detail-value"><?= $fullName ?: '—' ?></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Email</div>
                  <div class="detail-value"><?= $email ?: '—' ?></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Phone</div>
                  <div class="detail-value"><?= $phone ?: '—' ?></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Role</div>
                  <div class="detail-value"><?= $role ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal-overlay" id="editProfileModal">
    <div class="modal-box lg">
      <div class="modal-header">
        <h5><i class="fa-solid fa-pen-to-square"></i> Edit Profile</h5>
        <button class="modal-close" data-modal-close="editProfileModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="<?= BASE_URL ?>/actions/admin/update-profile.php">
          <?= csrfField() ?>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" name="full_name" value="<?= $fullName ?>" required />
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="<?= $email ?>" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-control" name="phone" value="<?= $phone ?>" placeholder="+63 9XX XXX XXXX" />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-modal-close="editProfileModal">Cancel</button>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Change Password Modal -->
  <div class="modal-overlay" id="changePasswordModal">
    <div class="modal-box sm">
      <div class="modal-header">
        <h5><i class="fa-solid fa-key"></i> Change Password</h5>
        <button class="modal-close" data-modal-close="changePasswordModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="<?= BASE_URL ?>/actions/admin/change-password.php">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current_password" required />
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" minlength="8" required placeholder="Minimum 8 characters" />
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" required placeholder="Repeat new password" />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-modal-close="changePasswordModal">Cancel</button>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-lock"></i> Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php
$extraScripts = <<<'JS'
<script>
  document.querySelectorAll(".status-badge-wrap").forEach(el => { el.innerHTML = statusBadge(el.dataset.status); });
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
