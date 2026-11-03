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

// Handle Admin Profile Photo Upload
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
            $photoFilename = 'admin_' . $adminId . '_' . time() . '.' . $ext;
            $destDir = __DIR__ . '/../../assets/uploads/avatars/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            if (move_uploaded_file($fileTmp, $destDir . $photoFilename)) {
                // Remove previous photo
                $oldPhoto = db()->prepare("SELECT profile_photo FROM admin_users WHERE id = ?");
                $oldPhoto->execute([$adminId]);
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
    redirectTo('/views/admin/profile.php');
}

try {
    if ($photoFilename !== null) {
        $stmt = db()->prepare("UPDATE admin_users SET full_name=?, email=?, phone=?, profile_photo=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $photoFilename, $adminId]);
    } else {
        $stmt = db()->prepare("UPDATE admin_users SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $adminId]);
    }

    // Refresh session
    $row = db()->prepare("SELECT id, username, full_name, email, phone, role, profile_photo FROM admin_users WHERE id=?");
    $row->execute([$adminId]);
    setAdminSession($row->fetch(PDO::FETCH_ASSOC));

    flashMessage('profile_success', 'Profile updated successfully.', 'success');
    redirectTo('/views/admin/profile.php');

} catch (\PDOException $e) {
    flashMessage('profile_error', 'A database error occurred. Please try again.', 'danger');
    redirectTo('/views/admin/profile.php');
}
