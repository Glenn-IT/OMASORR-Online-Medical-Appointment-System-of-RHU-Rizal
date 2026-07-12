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

$lockout = $_SESSION['login_lockout'] ?? null;
if ($lockout && $lockout['until'] <= time()) {
    unset($_SESSION['login_lockout']);
    $lockout = null;
}

$forgotNotice = getFlash('forgot_notice');
$forgotError  = getFlash('forgot_error');
$pwdReset     = $_SESSION['pwd_reset'] ?? null;
$forgotStep   = ($pwdReset && $pwdReset['account_type'] === 'patient') ? $pwdReset['step'] : 'email';

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
        <input type="hidden" name="account_type" value="patient" />
        <div class="modal-body">
          <p style="font-size:13.5px;color:#555;margin-bottom:16px">
            Enter your registered email address and we'll send you a 6-digit code.
          </p>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-group">
              <i class="fa-solid fa-envelope input-icon"></i>
              <input type="email" class="form-control" name="email" placeholder="your@email.com" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-close="forgotModal">Cancel</button>
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
          <button type="button" class="btn btn-secondary" data-modal-close="forgotModal">Cancel</button>
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
          <button type="button" class="btn btn-secondary" data-modal-close="forgotModal">Cancel</button>
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
  if ({$reopenForgot}) { document.addEventListener('DOMContentLoaded', () => openModal('forgotModal')); }

  document.getElementById('togglePwd')?.addEventListener('click', () => {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('togglePwd');
    if (pwd.type === 'password') { pwd.type = 'text';     icon.classList.replace('fa-eye','fa-eye-slash'); }
    else                         { pwd.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
  });

  (function () {
    const lockUntil = {$lockoutUntil} * 1000;
    if (!lockUntil || lockUntil <= Date.now()) return;

    const btn  = document.getElementById('loginBtn');
    const user = document.getElementById('username');
    const pass = document.getElementById('password');
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
require_once __DIR__ . '/includes/footer.php';
?>
