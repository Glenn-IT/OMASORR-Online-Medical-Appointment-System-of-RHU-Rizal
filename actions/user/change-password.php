<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin('patient');
verifyCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/user/profile.php');
}

$sess      = getPatientSession();
$patientId = (int) $sess['id'];

$current = $_POST['current_password'] ?? '';
$newPwd  = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$errors = [];
if (!$current || !$newPwd || !$confirm) $errors[] = 'All password fields are required.';
if ($newPwd !== $confirm)               $errors[] = 'New passwords do not match.';
if (strlen($newPwd) < 8)               $errors[] = 'New password must be at least 8 characters.';

if ($errors) {
    flashMessage('password_error', implode(' ', $errors), 'danger');
    redirectTo('/views/user/profile.php');
}

try {
    // Get user_id from patient
    $stmt = db()->prepare("SELECT u.id, u.password FROM patients p JOIN users u ON u.id=p.user_id WHERE p.id=? LIMIT 1");
    $stmt->execute([$patientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current, $row['password'])) {
        flashMessage('password_error', 'Current password is incorrect.', 'danger');
        redirectTo('/views/user/profile.php');
    }

    $newHash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $upd = db()->prepare("UPDATE users SET password=? WHERE id=?");
    $upd->execute([$newHash, $row['id']]);

    flashMessage('password_success', 'Password changed successfully.', 'success');
    redirectTo('/views/user/profile.php');

} catch (\PDOException $e) {
    flashMessage('password_error', 'A database error occurred. Please try again.', 'danger');
    redirectTo('/views/user/profile.php');
}
