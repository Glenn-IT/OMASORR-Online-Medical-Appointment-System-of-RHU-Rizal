<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/doctors.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    flashMessage('doctor_error', 'Invalid doctor.', 'danger');
    redirectTo('/views/admin/doctors.php');
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT available FROM doctors WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        flashMessage('doctor_error', 'Doctor not found.', 'danger');
        redirectTo('/views/admin/doctors.php');
    }

    $newAvailable = $doc['available'] ? 0 : 1;
    $pdo->prepare("UPDATE doctors SET available = ? WHERE id = ?")
        ->execute([$newAvailable, $id]);

    $label = $newAvailable ? 'Available' : 'Unavailable';
    flashMessage('doctor_success', "Doctor marked as {$label}.", 'success');
} catch (Exception $e) {
    flashMessage('doctor_error', 'Database error: ' . $e->getMessage(), 'danger');
}

redirectTo('/views/admin/doctors.php');
?>
