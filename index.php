<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

// Redirect if already logged in
if (isLoggedIn('patient')) {
    redirectTo('/views/user/dashboard.php');
}
if (isLoggedIn('admin')) {
    redirectTo('/views/admin/dashboard.php');
}

$flash        = getFlash('login_error');
$flashSuccess = getFlash('signup_success');

$pageTitle = 'Login – RHU Rizal Appointment System';
require_once __DIR__ . '/includes/header.php';
?>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="logo-icon"><i class="fa-solid fa-hospital-user"></i></div>
        <h2>RHU Rizal<br />Appointment System</h2>
        <p>Rural Health Unit – Municipality of Rizal</p>
      </div>

      <?php if ($flashSuccess): ?>
      <div class="alert alert-success mb-2" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= htmlspecialchars($flashSuccess['message']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-2" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= htmlspecialchars($flash['message']) ?></div>
      </div>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/actions/login.php" autocomplete="off">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-group">
            <i class="fa-solid fa-user input-icon"></i>
            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" />
            <i class="fa-solid fa-eye input-icon-right" id="togglePwd" style="pointer-events:all"></i>
          </div>
        </div>

        <div class="flex-between mb-2">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <input type="checkbox" id="rememberMe" /> Remember me
          </label>
          <a href="#" class="link-primary" style="font-size:13px" onclick="openModal('forgotModal');return false;">
            Forgot Password?
          </a>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>

        <div class="section-divider mt-3">or</div>

        <a href="<?= $base ?>/views/user/signup.php" class="btn btn-outline-primary btn-block" style="text-align:center;justify-content:center;">
          <i class="fa-solid fa-user-plus"></i> Create New Account
        </a>

        <div class="text-center mt-2">
          <a href="<?= $base ?>/views/admin/login.php" class="link-primary" style="font-size:12px">
            <i class="fa-solid fa-shield-halved"></i> Admin Login
          </a>
        </div>
      </form>

    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div class="modal-overlay" id="forgotModal">
    <div class="modal-box sm">
      <div class="modal-header">
        <h5><i class="fa-solid fa-key"></i> Forgot Password</h5>
        <button class="modal-close" data-modal-close="forgotModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <p style="font-size:13.5px;color:#555;margin-bottom:16px">
          Enter your registered email address and we'll send you a password reset link.
        </p>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-group">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" class="form-control" placeholder="your@email.com" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-modal-close="forgotModal">Cancel</button>
        <button class="btn btn-primary" onclick="showToast('Password reset link sent! (Prototype – UI only)','info');closeModal('forgotModal');">
          <i class="fa-solid fa-paper-plane"></i> Send Reset Link
        </button>
      </div>
    </div>
  </div>

<?php
$extraScripts = <<<'JS'
<script>
  document.getElementById('togglePwd')?.addEventListener('click', () => {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('togglePwd');
    if (pwd.type === 'password') { pwd.type = 'text';     icon.classList.replace('fa-eye','fa-eye-slash'); }
    else                         { pwd.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
  });
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
