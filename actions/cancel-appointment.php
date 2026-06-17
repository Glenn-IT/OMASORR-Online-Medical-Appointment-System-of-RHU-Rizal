<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/mailer.php';

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
        SELECT a.id, a.appt_no, a.status, a.date, a.time, a.service,
               p.full_name AS patient_name, p.email AS patient_email,
               d.name AS doctor_name
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN doctors  d ON d.id = a.doctor_id
        WHERE a.id = ? AND a.patient_id = ?
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

    // Send cancellation confirmation email
    if (!empty($appt['patient_email'])) {
        sendCancellationEmail($appt['patient_email'], $appt['patient_name'], [
            'appt_no' => $appt['appt_no'],
            'date'    => $appt['date'],
            'time'    => $appt['time'],
            'service' => $appt['service'],
            'doctor'  => $appt['doctor_name'],
        ]);
    }

    flashMessage('book_success', "Appointment {$appt['appt_no']} has been cancelled.", 'info');
    redirectTo('/views/user/my-appointments.php');

} catch (RuntimeException $e) {
    flashMessage('appt_error', 'A server error occurred. Please try again.', 'danger');
    redirectTo('/views/user/my-appointments.php');
}
