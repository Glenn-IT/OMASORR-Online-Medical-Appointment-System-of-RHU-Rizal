<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

if (isLoggedIn('admin')) {
    redirectTo('/views/admin/dashboard.php');
}

$flash = getFlash('admin_login_error');

$lockout = $_SESSION['admin_login_lockout'] ?? null;
if ($lockout && $lockout['until'] <= time()) {
    unset($_SESSION['admin_login_lockout']);
    $lockout = null;
}

$forgotNotice = getFlash('admin_forgot_notice');
$forgotError  = getFlash('admin_forgot_error');
$pwdReset     = $_SESSION['pwd_reset'] ?? null;
$forgotStep   = ($pwdReset && $pwdReset['account_type'] === 'admin') ? $pwdReset['step'] : 'email';

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

        <div class="flex-between mb-2">
          <span></span>
          <a href="#" class="link-primary" style="font-size:13px" onclick="openModal('adminForgotModal');return false;">
            Forgot Password?
          </a>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px" id="adminLoginBtn">
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

  <!-- Admin Forgot Password Modal -->
  <div class="modal-overlay" id="adminForgotModal">
    <div class="modal-box sm">
      <div class="modal-header">
        <h5><i class="fa-solid fa-key"></i> Forgot Password</h5>
        <button class="modal-close" data-modal-close="adminForgotModal"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <?php if ($forgotNotice): ?>
      <div class="alert alert-<?= htmlspecialchars($forgotNotice['type']) ?> mb-2" role="alert" style="margin:16px 20px 0">
        <div><?= htmlspecialchars($forgotNotice['message']) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($forgotError): ?>
      <div class="alert alert-<?= htmlspecialchars($forgotError['type']) ?> mb-2" role="alert" style="margin:16px 20px 0">
        <div><?= htmlspecialchars($forgotError['message']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($forgotStep === 'email'): ?>
      <form method="post" action="<?= BASE_URL ?>/actions/forgot-password/request.php">
        <?= csrfField() ?>
        <input type="hidden" name="account_type" value="admin" />
        <div class="modal-body">
          <p style="font-size:13.5px;color:#555;margin-bottom:16px">
            Enter your registered admin email address and we'll send you a 6-digit code.
          </p>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-group">
              <i class="fa-solid fa-envelope input-icon"></i>
              <input type="email" class="form-control" name="email" placeholder="admin@email.com" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-close="adminForgotModal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-paper-plane"></i> Send Code
          </button>
        </div>
      </form>

      <?php elseif ($forgotStep === 'otp'): ?>
      <form method="post" action="<?= BASE_URL ?>/actions/forgot-password/verify.php">
        <?= csrfField() ?>
        <div class="modal-body">
          <p style="font-size:13.5px;color:#555;margin-bottom:16px">
            Enter the 6-digit code we sent to your email. It expires in 10 minutes.
          </p>
          <div class="form-group">
            <label class="form-label">6-Digit Code</label>
            <div class="input-group">
              <i class="fa-solid fa-key input-icon"></i>
              <input type="text" class="form-control" name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-close="adminForgotModal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-check"></i> Verify Code
          </button>
        </div>
      </form>

      <?php else: ?>
      <form method="post" action="<?= BASE_URL ?>/actions/forgot-password/reset.php">
        <?= csrfField() ?>
        <div class="modal-body">
          <p style="font-size:13.5px;color:#555;margin-bottom:16px">
            Choose a new password for your account.
          </p>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="input-group">
              <i class="fa-solid fa-lock input-icon"></i>
              <input type="password" class="form-control" name="new_password" placeholder="At least 8 characters" required minlength="8" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
              <i class="fa-solid fa-lock input-icon"></i>
              <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter password" required minlength="8" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-close="adminForgotModal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-rotate"></i> Reset Password
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

<?php
$lockoutUntil = $lockout['until'] ?? 0;
$reopenForgot = ($forgotStep !== 'email' || $forgotNotice || $forgotError) ? 'true' : 'false';
$extraScripts = <<<JS
<script>
  if ({$reopenForgot}) { document.addEventListener('DOMContentLoaded', () => openModal('adminForgotModal')); }

  document.getElementById('toggleAdminPwd')?.addEventListener('click', () => {
    const pwd  = document.getElementById('adminPassword');
    const icon = document.getElementById('toggleAdminPwd');
    if (pwd.type === 'password') { pwd.type = 'text';     icon.classList.replace('fa-eye','fa-eye-slash'); }
    else                         { pwd.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
  });

  (function () {
    const lockUntil = {$lockoutUntil} * 1000;
    if (!lockUntil || lockUntil <= Date.now()) return;

    const btn  = document.getElementById('adminLoginBtn');
    const user = document.getElementById('adminUsername');
    const pass = document.getElementById('adminPassword');
    const label = btn.innerHTML;

    function tick() {
      const remaining = Math.ceil((lockUntil - Date.now()) / 1000);
      if (remaining <= 0) {
        btn.disabled = false; user.disabled = false; pass.disabled = false;
        btn.innerHTML = label;
        clearInterval(timer);
        return;
      }
      btn.disabled = true; user.disabled = true; pass.disabled = true;
      btn.innerHTML = 'Try again in ' + remaining + 's';
    }
    const timer = setInterval(tick, 250);
    tick();
  })();
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
