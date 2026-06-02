<?php
// ============================================================
// RHU Rizal — Session & Authentication Helpers
// ============================================================

require_once __DIR__ . '/config.php';

// ── Start session once ──────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    // Harden session cookie
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true on HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── CSRF helpers ─────────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ────────────────────────────────────────────────────────────
// requireLogin($role)
//
// Call at the top of every protected page.
// $role must be 'patient' or 'admin'.
//
// Redirects to the appropriate login page if the session
// for that role does not exist or has expired.
// ────────────────────────────────────────────────────────────
function requireLogin(string $role = 'patient'): void
{
    // Prevent browser from caching authenticated pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

    if ($role === 'admin') {
        if (empty($_SESSION['admin'])) {
            header('Location: ' . BASE_URL . '/views/admin/login.php');
            exit;
        }
    } else {
        if (empty($_SESSION['patient'])) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}

// ────────────────────────────────────────────────────────────
// getPatientSession()
//
// Returns the current patient's session array or null.
// Array keys: id, patient_no, full_name, username, status
// ────────────────────────────────────────────────────────────
function getPatientSession(): ?array
{
    return $_SESSION['patient'] ?? null;
}

// ────────────────────────────────────────────────────────────
// getAdminSession()
//
// Returns the current admin's session array or null.
// Array keys: id, full_name, username, email, role
// ────────────────────────────────────────────────────────────
function getAdminSession(): ?array
{
    return $_SESSION['admin'] ?? null;
}

// ────────────────────────────────────────────────────────────
// setPatientSession($patient, $user)
//
// Called after successful patient login.
// $patient = row from patients table
// $user    = row from users table
// ────────────────────────────────────────────────────────────
function setPatientSession(array $patient, array $user): void
{
    $_SESSION['patient'] = [
        'id'         => $patient['id'],
        'patient_no' => $patient['patient_no'],
        'full_name'  => $patient['full_name'],
        'username'   => $user['username'],
        'status'     => $user['status'],
    ];
}

// ────────────────────────────────────────────────────────────
// setAdminSession($admin)
//
// Called after successful admin login.
// $admin = row from admin_users table
// ────────────────────────────────────────────────────────────
function setAdminSession(array $admin): void
{
    $_SESSION['admin'] = [
        'id'        => $admin['id'],
        'full_name' => $admin['full_name'],
        'username'  => $admin['username'],
        'email'     => $admin['email'],
        'phone'     => $admin['phone'] ?? '',
        'role'      => $admin['role'],
    ];
}

// ────────────────────────────────────────────────────────────
// flashMessage($key, $message, $type)
//
// Store a one-time message in session (e.g. login errors).
// $type: 'success' | 'error' | 'warning' | 'info'
// ────────────────────────────────────────────────────────────
function flashMessage(string $key, string $message, string $type = 'info'): void
{
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

// ────────────────────────────────────────────────────────────
// getFlash($key)
//
// Retrieve and clear a flash message.
// Returns ['message' => '...', 'type' => '...'] or null.
// ────────────────────────────────────────────────────────────
function getFlash(string $key): ?array
{
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

// ────────────────────────────────────────────────────────────
// redirectIf($condition, $url)
//
// Convenience redirect helper.
// ────────────────────────────────────────────────────────────
function redirectTo(string $path): void
{
    // Prepend BASE_URL only if path doesn't start with http
    $url = (str_starts_with($path, 'http') ? '' : BASE_URL) . $path;
    header('Location: ' . $url);
    exit;
}

// ────────────────────────────────────────────────────────────
// isLoggedIn($role)
//
// Returns true if the given role has an active session.
// ────────────────────────────────────────────────────────────
function isLoggedIn(string $role = 'patient'): bool
{
    return $role === 'admin'
        ? !empty($_SESSION['admin'])
        : !empty($_SESSION['patient']);
}
