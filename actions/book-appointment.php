<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin('patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/user/book-appointment.php');
}
verifyCsrf();

$session   = getPatientSession();
$patientId = (int) $session['id'];
$username  = $session['username'];

$serviceId = (int) ($_POST['service_id'] ?? 0);
$doctorId  = (int) ($_POST['doctor_id']  ?? 0);
$date      = trim($_POST['date']   ?? '');
$time      = trim($_POST['time']   ?? '');
$reason    = trim($_POST['reason'] ?? '');

// Validation
$errors = [];
if (!$serviceId)                       $errors[] = 'Please select a service.';
if (!$doctorId)                        $errors[] = 'Please select a doctor.';
if (!$date || $date < date('Y-m-d'))   $errors[] = 'Please select a valid future date.';
if (!$time)                            $errors[] = 'Please select a time slot.';
if (!$reason)                          $errors[] = 'Please describe your reason for the visit.';

if ($errors) {
    flashMessage('book_error', implode(' ', $errors), 'danger');
    redirectTo('/views/user/book-appointment.php');
}

try {
    $pdo = db();

    // Prevent double-booking: same doctor, date, time
    $stmt = $pdo->prepare("
        SELECT id FROM appointments
        WHERE doctor_id = ? AND date = ? AND time = ?
          AND status NOT IN ('Cancelled','Rejected')
        LIMIT 1
    ");
    $stmt->execute([$doctorId, $date, $time]);
    if ($stmt->fetch()) {
        flashMessage('book_error', 'That time slot is already taken. Please choose another.', 'danger');
        redirectTo('/views/user/book-appointment.php');
    }

    // Get service name
    $svc = $pdo->prepare("SELECT name FROM services WHERE id = ? LIMIT 1");
    $svc->execute([$serviceId]);
    $serviceName = $svc->fetchColumn();
    if (!$serviceName) {
        flashMessage('book_error', 'Invalid service selected.', 'danger');
        redirectTo('/views/user/book-appointment.php');
    }

    // Generate appt_no
    $cnt    = (int) $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $apptNo = 'APT-' . str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO appointments (appt_no, patient_id, doctor_id, service, date, time, reason, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([$apptNo, $patientId, $doctorId, $serviceName, $date, $time, $reason]);
    $apptId = (int) $pdo->lastInsertId();

    // Audit log
    $log = $pdo->prepare("
        INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, note)
        VALUES (?, ?, '', 'Pending', 'Appointment booked by patient')
    ");
    $log->execute([$apptId, $username]);

    flashMessage('book_success', "Appointment {$apptNo} booked successfully! Awaiting approval.", 'success');
    redirectTo('/views/user/my-appointments.php');

} catch (RuntimeException $e) {
    flashMessage('book_error', 'A server error occurred. Please try again.', 'danger');
    redirectTo('/views/user/book-appointment.php');
}
