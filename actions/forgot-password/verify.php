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

if (!$state || $state['step'] !== 'otp') {
    flashMessage($errorKey, 'Please request a new code first.', 'warning');
    redirectTo($loginPage);
}

$code = trim($_POST['otp'] ?? '');

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT id, otp_hash, attempts, expires_at
        FROM password_resets
        WHERE id = ? AND account_type = ?
        LIMIT 1
    ");
    $stmt->execute([$state['reset_id'], $accountType]);
    $row = $stmt->fetch();

    $valid = $row
        && (int) $row['attempts'] < 5
        && strtotime($row['expires_at']) > time()
        && password_verify($code, $row['otp_hash']);

    if (!$valid) {
        if ($row) {
            $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?")
                ->execute([$row['id']]);
        }
        flashMessage($errorKey, 'Invalid or expired code. Please try again.', 'danger');
        redirectTo($loginPage);
    }

    $pdo->prepare("UPDATE password_resets SET verified_at = NOW() WHERE id = ?")
        ->execute([$row['id']]);

    $_SESSION['pwd_reset']['step'] = 'reset';

    flashMessage($errorKey, 'Code verified. Please set your new password.', 'success');
    redirectTo($loginPage);

} catch (RuntimeException | PDOException $e) {
    flashMessage($errorKey, 'A server error occurred. Please try again later.', 'danger');
    redirectTo($loginPage);
}
