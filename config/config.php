<?php
// ============================================================
// RHU Rizal — App Configuration
// ============================================================

// ── Timezone ────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Application constants ───────────────────────────────────
define('APP_NAME',    'RHU Rizal Online Medical Appointment System');
define('APP_SHORT',   'RHU Rizal');
define('APP_VERSION', '1.0.0');

// Base URL — no trailing slash.
// Change to '' (empty string) if deployed at the web root.
define('BASE_URL', '/rhu-appointment-system');

// ── Database credentials ────────────────────────────────────
// These are also used by database.php.
define('DB_HOST',     'localhost');
define('DB_NAME',     'rhu_rizal');
define('DB_USER',     'root');
define('DB_PASS',     '');           // XAMPP default: empty password
define('DB_CHARSET',  'utf8mb4');

// ── Session name ────────────────────────────────────────────
define('SESSION_NAME', 'rhu_session');

// ── Password hashing cost ───────────────────────────────────
define('BCRYPT_COST', 10);

// ── Gmail SMTP ──────────────────────────────────────────────
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'rhurizalcagayan@gmail.com');
define('MAIL_PASSWORD',  'frxh imov obxa jxfe');
define('MAIL_FROM',      'rhurizalcagayan@gmail.com');
define('MAIL_FROM_NAME', 'RHU Rizal Clinic');
