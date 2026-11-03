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
    $stmt = $pdo->prepare("SELECT id, name, specialty, available FROM doctors WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        flashMessage('doctor_error', 'Doctor not found.', 'danger');
        redirectTo('/views/admin/doctors.php');
    }

    $newAvailable = $doc['available'] ? 0 : 1;
    $pdo->prepare("UPDATE doctors SET available = ? WHERE id = ?")
        ->execute([$newAvailable, $id]);

    // Synchronize doctor unavailability announcement
    if ($newAvailable === 0) {
        $docName = $doc['name'];
        $docSpecialty = $doc['specialty'] ?? 'Specialist';
        $chkAnn = $pdo->prepare("SELECT id FROM announcements WHERE doctor_id = ? AND type = 'doctor_unavailable' LIMIT 1");
        $chkAnn->execute([$id]);
        $annId = $chkAnn->fetchColumn();
        if ($annId) {
            $pdo->prepare("UPDATE announcements SET is_active = 1 WHERE id = ?")->execute([$annId]);
        } else {
            $title = "Notice: {$docName} Unavailable";
            $msg   = "{$docName} ({$docSpecialty}) is temporarily unavailable for consultations and bookings. Please consult our other on-duty physicians.";
            $pdo->prepare("
                INSERT INTO announcements (title, message, type, doctor_id, is_active)
                VALUES (?, ?, 'doctor_unavailable', ?, 1)
            ")->execute([$title, $msg, $id]);
        }
    } else {
        $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE doctor_id = ? AND type = 'doctor_unavailable'")
            ->execute([$id]);
    }

    $label = $newAvailable ? 'Available' : 'Unavailable (Advisory notification broadcasted)';
    flashMessage('doctor_success', "Doctor marked as {$label}.", 'success');
} catch (Exception $e) {
    flashMessage('doctor_error', 'Database error: ' . $e->getMessage(), 'danger');
}

redirectTo('/views/admin/doctors.php');
