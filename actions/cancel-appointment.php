<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin('patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/user/my-appointments.php');
}
verifyCsrf();

$session   = getPatientSession();
$patientId = (int) $session['id'];
$username  = $session['username'];
$apptId    = (int) ($_POST['appointment_id'] ?? 0);

if (!$apptId) {
    flashMessage('appt_error', 'Invalid appointment.', 'danger');
    redirectTo('/views/user/my-appointments.php');
}

try {
    $pdo = db();

    // Verify the appointment belongs to this patient and is still Pending/Approved
    $stmt = $pdo->prepare("
        SELECT id, appt_no, status FROM appointments
        WHERE id = ? AND patient_id = ?
        LIMIT 1
    ");
    $stmt->execute([$apptId, $patientId]);
    $appt = $stmt->fetch();

    if (!$appt) {
        flashMessage('appt_error', 'Appointment not found.', 'danger');
        redirectTo('/views/user/my-appointments.php');
    }
    if (!in_array($appt['status'], ['Pending', 'Approved'])) {
        flashMessage('appt_error', 'Only Pending or Approved appointments can be cancelled.', 'warning');
        redirectTo('/views/user/my-appointments.php');
    }

    // Update status
    $upd = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
    $upd->execute([$apptId]);

    // Audit log
    $log = $pdo->prepare("
        INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, note)
        VALUES (?, ?, ?, 'Cancelled', 'Cancelled by patient')
    ");
    $log->execute([$apptId, $username, $appt['status']]);

    flashMessage('book_success', "Appointment {$appt['appt_no']} has been cancelled.", 'info');
    redirectTo('/views/user/my-appointments.php');

} catch (RuntimeException $e) {
    flashMessage('appt_error', 'A server error occurred. Please try again.', 'danger');
    redirectTo('/views/user/my-appointments.php');
}
