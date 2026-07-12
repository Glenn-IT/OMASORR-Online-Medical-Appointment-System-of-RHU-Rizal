<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/index.php');
}
verifyCsrf();

$accountType = ($_POST['account_type'] ?? 'patient') === 'admin' ? 'admin' : 'patient';
$loginPage   = $accountType === 'admin' ? '/views/admin/login.php' : '/index.php';
$noticeKey   = $accountType === 'admin' ? 'admin_forgot_notice' : 'forgot_notice';

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flashMessage($noticeKey, 'Please enter a valid email address.', 'warning');
    redirectTo($loginPage);
}

try {
    $pdo = db();

    if ($accountType === 'admin') {
        $stmt = $pdo->prepare("SELECT id, full_name FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        $accountId = $row ? (int) $row['id'] : null;
        $name      = $row['full_name'] ?? 'Admin';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id AS uid, p.full_name
            FROM users u JOIN patients p ON p.user_id = u.id
            WHERE p.email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        $accountId = $row ? (int) $row['uid'] : null;
        $name      = $row['full_name'] ?? 'there';
    }

    $resetId = 0;
    if ($accountId !== null) {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($code, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        $ins = $pdo->prepare("
            INSERT INTO password_resets (account_type, account_id, otp_hash, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        $ins->execute([$accountType, $accountId, $hash]);
        $resetId = (int) $pdo->lastInsertId();

        sendPasswordResetOtpEmail($email, $name, $code);
    }

    $_SESSION['pwd_reset'] = [
        'account_type' => $accountType,
        'reset_id'     => $resetId,
        'step'         => 'otp',
    ];

    flashMessage($noticeKey, 'If that email is registered, a 6-digit code has been sent to it.', 'info');
    redirectTo($loginPage);

} catch (RuntimeException | PDOException $e) {
    flashMessage($noticeKey, 'A server error occurred. Please try again later.', 'danger');
    redirectTo($loginPage);
}
