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
define('MAIL_USERNAME',  'prototypev1.03@gmail.com');
define('MAIL_PASSWORD',  'lqps acqk sbri hxrt');
define('MAIL_FROM',      'prototypev1.03@gmail.com');
define('MAIL_FROM_NAME', 'RHU Rizal Clinic');

// ── Clinic Schedule & Working Days Helpers ──────────────────
/**
 * Check if a given date (Y-m-d) is a clinic open weekday (Monday to Friday).
 * Weekends (Saturday and Sunday) are strictly closed.
 */
function isClinicOpenDate(string $date): bool
{
    $dayOfWeek = (int) date('N', strtotime($date)); // 1 (Mon) to 7 (Sun)
    return ($dayOfWeek >= 1 && $dayOfWeek <= 5);
}

/**
 * Parse doctor schedule string to an array of short weekday names: e.g. ['Mon', 'Tue', 'Wed', 'Fri']
 * Since RHU is closed on weekends, only weekdays (Mon-Fri) are valid duty days.
 */
function parseDoctorScheduleDays(string $schedule): array
{
    $s = trim($schedule);
    if (!$s) {
        return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    }

    $known = [
        'mon-fri'     => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        'mon-sat'     => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], // clamp to weekdays
        'mon-wed-fri' => ['Mon', 'Wed', 'Fri'],
        'tue-thu'     => ['Tue', 'Thu'],
        'mon-thu'     => ['Mon', 'Tue', 'Wed', 'Thu'],
        'wed-fri'     => ['Wed', 'Fri'],
        'tue-fri'     => ['Tue', 'Wed', 'Thu', 'Fri'],
    ];

    $key = strtolower($s);
    if (isset($known[$key])) {
        return $known[$key];
    }

    $validWeekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    $lowerMap = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri'];

    // Split on comma, slash, space, or hyphen
    $parts = preg_split('/[\s,\/]+/', $s);
    if (count($parts) === 1 && str_contains($s, '-')) {
        $parts = explode('-', $s);
    }

    $detected = [];
    foreach ($parts as $p) {
        $clean = strtolower(trim($p));
        if (isset($lowerMap[$clean])) {
            $detected[] = $lowerMap[$clean];
        } elseif (strlen($clean) >= 3 && isset($lowerMap[substr($clean, 0, 3)])) {
            $detected[] = $lowerMap[substr($clean, 0, 3)];
        }
    }

    if (!empty($detected)) {
        return array_values(array_unique($detected));
    }

    // Fallback: search for weekday abbreviations anywhere in text in order
    foreach ($lowerMap as $k => $v) {
        if (stripos($s, $k) !== false && !in_array($v, $detected, true)) {
            $detected[] = $v;
        }
    }

    return !empty($detected) ? $detected : $validWeekdays;
}

/**
 * Check if a doctor is on duty on a specific date (Y-m-d).
 * Returns false if the clinic is closed (weekends) or if the doctor is not scheduled on that weekday.
 */
function isDoctorOnDuty(string $schedule, string $date): bool
{
    if (!isClinicOpenDate($date)) {
        return false;
    }
    $dayShort = date('D', strtotime($date)); // 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'
    $dutyDays = parseDoctorScheduleDays($schedule);
    return in_array($dayShort, $dutyDays, true);
}

