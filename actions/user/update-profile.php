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

$fullName  = trim($_POST['full_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$address   = trim($_POST['address']    ?? '');
$bloodType = trim($_POST['blood_type'] ?? '');

$errors = [];
if (!$fullName)  $errors[] = 'Full name is required.';
if (!$phone)     $errors[] = 'Phone number is required.';
if (!$address)   $errors[] = 'Address is required.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

if ($errors) {
    flashMessage('profile_error', implode(' ', $errors), 'danger');
    redirectTo('/views/user/profile.php');
}

try {
    $stmt = db()->prepare("UPDATE patients SET full_name=?, email=?, phone=?, address=?, blood_type=? WHERE id=?");
    $stmt->execute([$fullName, $email, $phone, $address, $bloodType ?: null, $patientId]);

    // Refresh patient session
    $row = db()->prepare("SELECT p.*, u.username, u.status FROM patients p JOIN users u ON u.id=p.user_id WHERE p.id=?");
    $row->execute([$patientId]);
    $data = $row->fetch(PDO::FETCH_ASSOC);
    setPatientSession($data, ['username' => $data['username'], 'status' => $data['status']]);

    flashMessage('profile_success', 'Profile updated successfully.', 'success');
    redirectTo('/views/user/profile.php');

} catch (\PDOException $e) {
    flashMessage('profile_error', 'A database error occurred. Please try again.', 'danger');
    redirectTo('/views/user/profile.php');
}
