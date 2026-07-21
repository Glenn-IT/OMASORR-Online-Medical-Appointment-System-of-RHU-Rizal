<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin('admin');
verifyCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/profile.php');
}

$admin    = getAdminSession();
$adminId  = (int) $admin['id'];

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email']     ?? '');
$phone    = trim($_POST['phone']     ?? '');

$errors = [];
if (!$fullName)                                     $errors[] = 'Full name is required.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if ($phone && !preg_match('/^09\d{9}$/', $phone)) $errors[] = 'Phone number must be an 11-digit PH mobile number starting with 09.';

if ($errors) {
    flashMessage('profile_error', implode(' ', $errors), 'danger');
    redirectTo('/views/admin/profile.php');
}

try {
    $stmt = db()->prepare("UPDATE admin_users SET full_name=?, email=?, phone=? WHERE id=?");
    $stmt->execute([$fullName, $email, $phone, $adminId]);

    // Refresh session
    $row = db()->prepare("SELECT id, username, full_name, email, phone, role FROM admin_users WHERE id=?");
    $row->execute([$adminId]);
    setAdminSession($row->fetch(PDO::FETCH_ASSOC));

    flashMessage('profile_success', 'Profile updated successfully.', 'success');
    redirectTo('/views/admin/profile.php');

} catch (\PDOException $e) {
    flashMessage('profile_error', 'A database error occurred. Please try again.', 'danger');
    redirectTo('/views/admin/profile.php');
}
