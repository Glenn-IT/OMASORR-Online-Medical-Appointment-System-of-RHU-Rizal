<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/index.php');
}
verifyCsrf();

$state       = $_SESSION['pwd_reset'] ?? null;
$accountType = ($state['account_type'] ?? 'patient') === 'admin' ? 'admin' : 'patient';
$loginPage   = $accountType === 'admin' ? '/views/admin/login.php' : '/index.php';
$errorKey    = $accountType === 'admin' ? 'admin_forgot_error' : 'forgot_error';

if (!$state || $state['step'] !== 'reset') {
    flashMessage($errorKey, 'Please verify your code first.', 'warning');
    redirectTo($loginPage);
}

$newPwd  = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (strlen($newPwd) < 8) {
    flashMessage($errorKey, 'Password must be at least 8 characters.', 'danger');
    redirectTo($loginPage);
}
if ($newPwd !== $confirm) {
    flashMessage($errorKey, 'Passwords do not match.', 'danger');
    redirectTo($loginPage);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT id, account_id, verified_at, expires_at
        FROM password_resets
        WHERE id = ? AND account_type = ?
        LIMIT 1
    ");
    $stmt->execute([$state['reset_id'], $accountType]);
    $row = $stmt->fetch();

    if (!$row || !$row['verified_at'] || strtotime($row['expires_at']) < time()) {
        unset($_SESSION['pwd_reset']);
        flashMessage($errorKey, 'Your reset session has expired. Please start again.', 'danger');
        redirectTo($loginPage);
    }

    $hash  = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $table = $accountType === 'admin' ? 'admin_users' : 'users';

    $pdo->prepare("UPDATE {$table} SET password = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?")
        ->execute([$hash, $row['account_id']]);

    unset($_SESSION['pwd_reset']);

    $successKey = $accountType === 'admin' ? 'admin_login_error' : 'login_error';
    flashMessage($successKey, 'Password reset successfully. Please log in.', 'success');
    redirectTo($loginPage);

} catch (RuntimeException | PDOException $e) {
    flashMessage($errorKey, 'A server error occurred. Please try again later.', 'danger');
    redirectTo($loginPage);
}
