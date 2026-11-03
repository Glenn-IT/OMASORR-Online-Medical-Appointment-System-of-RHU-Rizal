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
elseif (!preg_match('/^09\d{9}$/', $phone)) $errors[] = 'Phone number must be an 11-digit PH mobile number starting with 09.';
if (!$address)   $errors[] = 'Address is required.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

// Handle Profile Photo Upload
$photoFilename = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $fileTmp  = $_FILES['profile_photo']['tmp_name'];
    $fileSize = $_FILES['profile_photo']['size'];
    $fileOrig = $_FILES['profile_photo']['name'];
    $ext = strtolower(pathinfo($fileOrig, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        $errors[] = 'Profile picture must be a JPG, PNG, or WEBP image.';
    } elseif ($fileSize > 2 * 1024 * 1024) {
        $errors[] = 'Profile picture must not exceed 2MB in size.';
    } else {
        $checkImg = @getimagesize($fileTmp);
        if ($checkImg === false) {
            $errors[] = 'Uploaded file is not a valid image.';
        } else {
            $photoFilename = 'patient_' . $patientId . '_' . time() . '.' . $ext;
            $destDir = __DIR__ . '/../../assets/uploads/avatars/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            if (move_uploaded_file($fileTmp, $destDir . $photoFilename)) {
                // Remove previous photo
                $oldPhoto = db()->prepare("SELECT profile_photo FROM patients WHERE id = ?");
                $oldPhoto->execute([$patientId]);
                $oldFile = $oldPhoto->fetchColumn();
                if ($oldFile && file_exists($destDir . $oldFile)) {
                    @unlink($destDir . $oldFile);
                }
            } else {
                $photoFilename = null;
                $errors[] = 'Failed to save uploaded image.';
            }
        }
    }
}

if ($errors) {
    flashMessage('profile_error', implode(' ', $errors), 'danger');
    redirectTo('/views/user/profile.php');
}

try {
    if ($photoFilename !== null) {
        $stmt = db()->prepare("UPDATE patients SET full_name=?, email=?, phone=?, address=?, blood_type=?, profile_photo=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $address, $bloodType ?: null, $photoFilename, $patientId]);
    } else {
        $stmt = db()->prepare("UPDATE patients SET full_name=?, email=?, phone=?, address=?, blood_type=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $address, $bloodType ?: null, $patientId]);
    }

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
