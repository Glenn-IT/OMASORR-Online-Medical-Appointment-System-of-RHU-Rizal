<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/login.php');
}
verifyCsrf();

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    flashMessage('admin_login_error', 'Please enter your username and password.', 'warning');
    redirectTo('/views/admin/login.php');
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT id, username, password AS password_hash, full_name, email, role,
               failed_attempts, locked_until
        FROM admin_users
        WHERE BINARY username = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && $admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
        $_SESSION['admin_login_lockout'] = ['until' => strtotime($admin['locked_until'])];
        flashMessage('admin_login_error', 'Account locked. Please wait 30 seconds before trying again.', 'danger');
        redirectTo('/views/admin/login.php');
    }

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        if ($admin) {
            $attempts = (int) $admin['failed_attempts'] + 1;
            if ($attempts >= 3) {
                $pdo->prepare("UPDATE admin_users SET failed_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL 30 SECOND) WHERE id = ?")
                    ->execute([$admin['id']]);
                $_SESSION['admin_login_lockout'] = ['until' => time() + 30];
                flashMessage('admin_login_error', 'Account locked due to too many failed attempts. Please wait 30 seconds before trying again.', 'danger');
            } else {
                $pdo->prepare("UPDATE admin_users SET failed_attempts = ? WHERE id = ?")
                    ->execute([$attempts, $admin['id']]);
                $remaining = 3 - $attempts;
                flashMessage('admin_login_error', "Invalid admin credentials. {$remaining} attempt(s) left before your account is locked.", 'danger');
            }
        } else {
            flashMessage('admin_login_error', 'Invalid admin credentials.', 'danger');
        }
        redirectTo('/views/admin/login.php');
    }

    $pdo->prepare("UPDATE admin_users SET failed_attempts = 0, locked_until = NULL WHERE id = ?")
        ->execute([$admin['id']]);
    unset($_SESSION['admin_login_lockout']);

    setAdminSession($admin);
    redirectTo('/views/admin/dashboard.php');

} catch (RuntimeException $e) {
    flashMessage('admin_login_error', 'A server error occurred. Please try again later.', 'danger');
    redirectTo('/views/admin/login.php');
}
