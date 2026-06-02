<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

if (isLoggedIn('patient')) {
    redirectTo('/views/user/dashboard.php');
}

$flash = getFlash('signup_error');

$pageTitle  = 'Sign Up – RHU Rizal Appointment System';
$extraHead  = <<<'CSS'
<style>
  .auth-card { max-width: 600px; }
  .step-indicator { display:flex;justify-content:center;gap:0;margin-bottom:28px; }
  .step { display:flex;align-items:center;gap:0; }
  .step-circle { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;background:var(--gray-200);color:var(--gray-500);border:2px solid var(--gray-300);transition:var(--transition); }
  .step.active .step-circle { background:var(--primary);color:#fff;border-color:var(--primary); }
  .step.done .step-circle { background:var(--secondary);color:#fff;border-color:var(--secondary); }
  .step-line { width:50px;height:2px;background:var(--gray-300); }
  .step-label { font-size:11px;color:var(--gray-500);text-align:center;margin-top:6px; }
  .step-block { display:flex;flex-direction:column;align-items:center; }
</style>
CSS;
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="logo-icon"><i class="fa-solid fa-user-plus"></i></div>
        <h2>Create Account</h2>
        <p>Register as a patient of RHU Rizal</p>
      </div>

      <!-- Step Indicator -->
      <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-2" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= htmlspecialchars($flash['message']) ?></div>
      </div>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/actions/register.php" id="signupForm" autocomplete="off">
      <?= csrfField() ?>
      <!-- Step Indicator -->

      <div class="step-indicator">
        <div class="step-block">
          <div class="step active" id="step1Indicator"><div class="step-circle">1</div></div>
          <div class="step-label">Personal Info</div>
        </div>
        <div class="step-line" style="margin-top:15px"></div>
        <div class="step-block">
          <div class="step" id="step2Indicator"><div class="step-circle">2</div></div>
          <div class="step-label">Account Setup</div>
        </div>
        <div class="step-line" style="margin-top:15px"></div>
        <div class="step-block">
          <div class="step" id="step3Indicator"><div class="step-circle">3</div></div>
          <div class="step-label">Done</div>
        </div>
      </div>

      <!-- Step 1: Personal Info -->
      <div id="step1">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" class="form-control" id="firstName" name="first_name" placeholder="Juan" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Dela Cruz" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date of Birth *</label>
            <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>" max="<?= date('Y-m-d') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label">Age</label>
            <input type="text" class="form-control" id="age" name="age" placeholder="Auto-calculated" readonly style="background:var(--gray-100);cursor:not-allowed;" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Gender *</label>
            <select class="form-select" id="gender" name="gender">
              <option value="">-- Select --</option>
              <option value="Male"   <?= ($_POST['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
              <option value="Other"  <?= ($_POST['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Blood Type</label>
            <select class="form-select" id="bloodType" name="blood_type">
              <option value="">-- Select --</option>
              <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-','Unknown'] as $bt): ?>
              <option <?= ($_POST['blood_type'] ?? '') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <div class="input-group">
              <i class="fa-solid fa-phone input-icon"></i>
              <input type="tel" class="form-control" id="phone" name="phone" placeholder="09XXXXXXXXX" maxlength="11" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
            </div>
            <p class="form-hint" id="phoneHint">Must be 11 digits starting with 09 (e.g. 09123456789)</p>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Complete Address *</label>
          <input type="text" class="form-control" id="address" name="address" placeholder="Brgy., Municipality, Province" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" />
        </div>
        <button class="btn btn-primary btn-block" onclick="goToStep2()">
          Next <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>

      <!-- Step 2: Account Setup -->
      <div id="step2" style="display:none">
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <div class="input-group">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" class="form-control" id="email" name="email" placeholder="you@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          </div>
          <p class="form-hint" id="emailHint" style="display:none;color:var(--danger)">Please enter a valid email address (e.g. user@example.com)</p>
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <div class="input-group">
            <i class="fa-solid fa-user input-icon"></i>
            <input type="text" class="form-control" id="regUsername" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
          </div>
          <p class="form-hint">Username must be at least 4 characters, no spaces.</p>
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <div class="input-group">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" class="form-control" id="regPassword" name="password" placeholder="At least 8 characters" />
            <i class="fa-solid fa-eye input-icon-right" id="toggleRegPwd" style="pointer-events:all;cursor:pointer" title="Show/hide password"></i>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <div class="input-group">
            <i class="fa-solid fa-shield-halved input-icon"></i>
            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Repeat password" />
            <i class="fa-solid fa-eye input-icon-right" id="toggleConfirmPwd" style="pointer-events:all;cursor:pointer" title="Show/hide password"></i>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;cursor:pointer;">
            <input type="checkbox" id="agreeTerms" style="margin-top:3px" />
            I agree to the Terms &amp; Conditions and Privacy Policy of RHU Rizal
          </label>
        </div>
        <div class="flex-gap">
          <button class="btn btn-secondary" type="button" onclick="goToStep1()">
            <i class="fa-solid fa-arrow-left"></i> Back
          </button>
          <button type="submit" class="btn btn-primary" style="flex:1" id="submitBtn">
            <i class="fa-solid fa-user-check"></i> Create Account
          </button>
        </div>
      </div>

      <!-- Step 3: Success -->
      <div id="step3" style="display:none;text-align:center">
        <div style="font-size:60px;color:var(--secondary);margin-bottom:16px">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h3 style="font-size:20px;color:var(--gray-800);margin-bottom:8px">Account Created!</h3>
        <p style="color:var(--gray-500);font-size:14px;margin-bottom:24px">
          Your account has been successfully registered.<br />
          You can now log in and book appointments.
        </p>
        <a href="<?= $base ?>/index.php" class="btn btn-primary btn-block">
          <i class="fa-solid fa-right-to-bracket"></i> Go to Login
        </a>
      </div>

      </form><!-- /signupForm -->

      <p class="text-center mt-2" style="font-size:13px" id="loginLink">
        Already have an account?
        <a href="<?= $base ?>/index.php" class="link-primary">Sign In</a>
      </p>
    </div>
  </div>

<?php
$extraScripts = <<<'JS'
<script>
  // ── Age auto-calculation ──────────────────────────────────
  function calculateAge(dob) {
    if (!dob) return '';
    const today = new Date();
    const birth = new Date(dob);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age >= 0 ? age : '';
  }

  document.getElementById('birthdate')?.addEventListener('change', function () {
    document.getElementById('age').value = calculateAge(this.value);
  });

  // Pre-fill age if birthdate already has a value (e.g. after validation failure)
  (function () {
    const bd = document.getElementById('birthdate')?.value;
    if (bd) document.getElementById('age').value = calculateAge(bd);
  })();

  // ── Phone validation helper ───────────────────────────────
  function isValidPHPhone(val) {
    return /^09\d{9}$/.test(val);
  }

  document.getElementById('phone')?.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
    const hint = document.getElementById('phoneHint');
    if (this.value && !isValidPHPhone(this.value)) {
      hint.style.color = 'var(--danger)';
    } else {
      hint.style.color = '';
    }
  });

  // ── Email validation helper ───────────────────────────────
  function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
  }

  document.getElementById('email')?.addEventListener('blur', function () {
    const hint = document.getElementById('emailHint');
    if (this.value && !isValidEmail(this.value)) {
      hint.style.display = '';
    } else {
      hint.style.display = 'none';
    }
  });

  // ── Password show/hide toggles ────────────────────────────
  function makeToggle(toggleId, inputId) {
    document.getElementById(toggleId)?.addEventListener('click', function () {
      const inp = document.getElementById(inputId);
      if (inp.type === 'password') {
        inp.type = 'text';
        this.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        inp.type = 'password';
        this.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  }
  makeToggle('toggleRegPwd',     'regPassword');
  makeToggle('toggleConfirmPwd', 'confirmPassword');

  // ── Step navigation ───────────────────────────────────────
  function goToStep2() {
    const fields = ['firstName','lastName','birthdate','gender','phone','address'];
    if (fields.some(id => !document.getElementById(id).value.trim())) {
      showToast('Please fill in all required fields.', 'warning'); return;
    }
    const phone = document.getElementById('phone').value.trim();
    if (!isValidPHPhone(phone)) {
      showToast('Phone number must be 11 digits starting with 09 (e.g. 09123456789).', 'warning'); return;
    }
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    document.getElementById('step1Indicator').classList.replace('active', 'done');
    document.getElementById('step2Indicator').classList.add('active');
  }

  function goToStep1() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2Indicator').classList.remove('active');
    document.getElementById('step1Indicator').classList.remove('done');
    document.getElementById('step1Indicator').classList.add('active');
  }

  // ── Form submit validation ────────────────────────────────
  document.getElementById('signupForm')?.addEventListener('submit', (e) => {
    const email    = document.getElementById('email').value.trim();
    const username = document.getElementById('regUsername').value.trim();
    const password = document.getElementById('regPassword').value;
    const confirm  = document.getElementById('confirmPassword').value;
    const agree    = document.getElementById('agreeTerms').checked;

    if (!email || !username || !password || !confirm) {
      e.preventDefault(); showToast('Please fill in all required fields.', 'warning'); return;
    }
    if (!isValidEmail(email)) {
      e.preventDefault();
      document.getElementById('emailHint').style.display = '';
      showToast('Please enter a valid email address.', 'warning'); return;
    }
    if (username.length < 4 || username.includes(' ')) {
      e.preventDefault(); showToast('Username must be at least 4 characters and no spaces.', 'warning'); return;
    }
    if (password.length < 8) {
      e.preventDefault(); showToast('Password must be at least 8 characters.', 'warning'); return;
    }
    if (password !== confirm) {
      e.preventDefault(); showToast('Passwords do not match.', 'error'); return;
    }
    if (!agree) {
      e.preventDefault(); showToast('You must agree to the Terms & Conditions.', 'warning'); return;
    }

    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account...';
    btn.disabled = true;
  });
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
