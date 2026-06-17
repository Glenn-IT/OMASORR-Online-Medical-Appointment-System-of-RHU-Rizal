<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

if (isLoggedIn('admin')) {
    redirectTo('/views/admin/dashboard.php');
}

$flash = getFlash('admin_login_error');

$pageTitle = 'Admin Login – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="logo-icon" style="background:#1a202c;color:#fff">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h2>Admin Portal</h2>
        <p>RHU Rizal – System Administrator</p>
      </div>

      <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-2" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= htmlspecialchars($flash['message']) ?></div>
      </div>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/actions/admin/login.php" autocomplete="off">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Admin Username</label>
          <div class="input-group">
            <i class="fa-solid fa-user-shield input-icon"></i>
            <input type="text" class="form-control" id="adminUsername" name="username" placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="••••••••" />
            <i class="fa-solid fa-eye input-icon-right" id="toggleAdminPwd" style="pointer-events:all"></i>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In as Admin
        </button>

        <div class="text-center mt-2">
          <a href="<?= BASE_URL ?>/index.php" class="link-primary" style="font-size:12px">
            <i class="fa-solid fa-arrow-left"></i> Back to Patient Login
          </a>
        </div>
      </form>

    </div>
  </div>

<?php
$extraScripts = <<<'JS'
<script>
  document.getElementById('toggleAdminPwd')?.addEventListener('click', () => {
    const pwd  = document.getElementById('adminPassword');
    const icon = document.getElementById('toggleAdminPwd');
    if (pwd.type === 'password') { pwd.type = 'text';     icon.classList.replace('fa-eye','fa-eye-slash'); }
    else                         { pwd.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
  });
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
