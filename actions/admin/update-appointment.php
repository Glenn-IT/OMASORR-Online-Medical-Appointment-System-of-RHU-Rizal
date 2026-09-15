<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/mailer.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/appointments.php');
}
verifyCsrf();

$session  = getAdminSession();
$username = $session['username'];

$apptId   = (int)   ($_POST['appointment_id'] ?? 0);
$newStatus = trim(  $_POST['status']          ?? '');
$note      = trim(  $_POST['note']            ?? '');

$allowed = ['Approved', 'Rejected', 'Completed', 'Cancelled'];
if (!$apptId || !in_array($newStatus, $allowed)) {
    flashMessage('appt_error', 'Invalid request.', 'danger');
    redirectTo('/views/admin/appointments.php');
}

if (in_array($newStatus, ['Cancelled', 'Rejected']) && $note === '') {
    flashMessage('appt_error', 'A reason is required when cancelling or rejecting an appointment.', 'danger');
    redirectTo('/views/admin/appointments.php');
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT a.id, a.appt_no, a.status, a.date, a.time, a.service, a.reason, a.doctor_id,
               p.id AS patient_id, p.full_name AS patient_name, p.patient_no, p.email AS patient_email,
               p.birthdate, p.gender, p.address,
               d.name AS doctor_name
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        LEFT JOIN doctors d ON d.id = a.doctor_id
        WHERE a.id = ? LIMIT 1
    ");
    $stmt->execute([$apptId]);
    $appt = $stmt->fetch();

    if (!$appt) {
        flashMessage('appt_error', 'Appointment not found.', 'danger');
        redirectTo('/views/admin/appointments.php');
    }

    $oldStatus = $appt['status'];

    // Business rules
    $transitions = [
        'Pending'   => ['Approved', 'Rejected'],
        'Approved'  => ['Completed', 'Cancelled'],
    ];
    if (!isset($transitions[$oldStatus]) || !in_array($newStatus, $transitions[$oldStatus])) {
        flashMessage('appt_error', "Cannot transition from {$oldStatus} to {$newStatus}.", 'warning');
        redirectTo('/views/admin/appointments.php');
    }

    $pdo->beginTransaction();

    // Update status (and optionally date/time for reschedule)
    $newDate = trim($_POST['new_date'] ?? '');
    $newTime = trim($_POST['new_time'] ?? '');
    if (in_array($newStatus, ['Cancelled', 'Rejected'])) {
        $pdo->prepare("UPDATE appointments SET status = ?, admin_note = ? WHERE id = ?")
            ->execute([$newStatus, $note, $apptId]);
    } elseif ($newDate && $newTime) {
        $pdo->prepare("UPDATE appointments SET status = ?, date = ?, time = ? WHERE id = ?")
            ->execute([$newStatus, $newDate, $newTime, $apptId]);
    } else {
        $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?")
            ->execute([$newStatus, $apptId]);
    }

    // When marking Completed, persist consultation record
    if ($newStatus === 'Completed') {
        $modeOfTransaction = trim($_POST['mode_of_transaction'] ?? '') ?: 'Online appointment';
        $consultDate       = trim($_POST['consultation_date'] ?? '') ?: ($appt['date'] ?: date('Y-m-d'));
        $consultTime       = trim($_POST['consultation_time'] ?? '') ?: ($appt['time'] ?: date('H:i:s'));
        $patientName       = trim($_POST['patient_name'] ?? '') ?: $appt['patient_name'];
        $dob               = trim($_POST['dob'] ?? '') ?: ($appt['birthdate'] ?? null);
        $gender            = trim($_POST['gender'] ?? '') ?: ($appt['gender'] ?? null);
        $address           = trim($_POST['address'] ?? '') ?: ($appt['address'] ?? null);
        $folderNo          = trim($_POST['folder_no'] ?? '') ?: $appt['patient_no'];
        $tinNo             = trim($_POST['tin_no'] ?? '') ?: null;
        $natureOfVisit     = trim($_POST['nature_of_visit'] ?? '') ?: ($appt['reason'] ?? '');

        // Age calculation
        $ageInput = trim($_POST['age'] ?? '');
        $age = null;
        if ($ageInput !== '' && is_numeric($ageInput)) {
            $age = (int) $ageInput;
        } elseif ($dob) {
            try {
                $bDate = new DateTime($dob);
                $today = new DateTime('today');
                $age = $bDate->diff($today)->y;
            } catch (Exception $e) {
                $age = null;
            }
        }

        $chiefComplaints   = trim($_POST['chief_complaints'] ?? '') ?: null;
        $height            = trim($_POST['height'] ?? '') ?: null;
        $weight            = trim($_POST['weight'] ?? '') ?: null;
        $bp                = trim($_POST['bp'] ?? '') ?: null;
        $rr                = trim($_POST['rr'] ?? '') ?: null;
        $pr                = trim($_POST['pr'] ?? '') ?: null;
        $temperature       = trim($_POST['temperature'] ?? '') ?: null;
        $historyOfIllness  = trim($_POST['history_of_illness'] ?? '') ?: null;
        $pastMedHistory    = trim($_POST['past_medical_history'] ?? '') ?: null;
        $pertinentPe       = trim($_POST['pertinent_pe'] ?? '') ?: null;
        $diagnosis         = trim($_POST['diagnosis'] ?? '') ?: null;
        $treatment         = trim($_POST['treatment'] ?? '') ?: null;
        $labFindings       = trim($_POST['lab_findings'] ?? '') ?: null;

        $consultStmt = $pdo->prepare("
            INSERT INTO consultation_records (
                appointment_id, patient_id, doctor_id, patient_name, dob, age, gender, address, folder_no, tin_no,
                mode_of_transaction, consultation_date, consultation_time, nature_of_visit,
                chief_complaints, height, weight, bp, rr, pr, temperature,
                history_of_illness, past_medical_history, pertinent_pe, diagnosis, treatment, lab_findings,
                created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?
            )
            ON DUPLICATE KEY UPDATE
                doctor_id = VALUES(doctor_id),
                patient_name = VALUES(patient_name),
                dob = VALUES(dob),
                age = VALUES(age),
                gender = VALUES(gender),
                address = VALUES(address),
                folder_no = VALUES(folder_no),
                tin_no = VALUES(tin_no),
                mode_of_transaction = VALUES(mode_of_transaction),
                consultation_date = VALUES(consultation_date),
                consultation_time = VALUES(consultation_time),
                nature_of_visit = VALUES(nature_of_visit),
                chief_complaints = VALUES(chief_complaints),
                height = VALUES(height),
                weight = VALUES(weight),
                bp = VALUES(bp),
                rr = VALUES(rr),
                pr = VALUES(pr),
                temperature = VALUES(temperature),
                history_of_illness = VALUES(history_of_illness),
                past_medical_history = VALUES(past_medical_history),
                pertinent_pe = VALUES(pertinent_pe),
                diagnosis = VALUES(diagnosis),
                treatment = VALUES(treatment),
                lab_findings = VALUES(lab_findings),
                created_by = VALUES(created_by)
        ");
        $consultStmt->execute([
            $apptId, (int) $appt['patient_id'], $appt['doctor_id'], $patientName, $dob, $age, $gender, $address, $folderNo, $tinNo,
            $modeOfTransaction, $consultDate, $consultTime, $natureOfVisit,
            $chiefComplaints, $height, $weight, $bp, $rr, $pr, $temperature,
            $historyOfIllness, $pastMedHistory, $pertinentPe, $diagnosis, $treatment, $labFindings,
            $username
        ]);
    }

    // Audit log
    $logNote = $note ?: ($newStatus === 'Completed' ? "Consultation completed and recorded by admin" : "Status changed by admin");
    $pdo->prepare("
        INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, note)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$apptId, $username, $oldStatus, $newStatus, $logNote]);

    $pdo->commit();

    // Send email notification to patient
    if (!empty($appt['patient_email'])) {
        sendAppointmentStatusEmail(
            $appt['patient_email'],
            $appt['patient_name'],
            [
                'appt_no' => $appt['appt_no'],
                'date'    => $appt['date'],
                'time'    => $appt['time'],
                'service' => $appt['service'],
                'doctor'  => $appt['doctor_name'],
            ],
            $newStatus,
            $note
        );
    }

    $msgs = [
        'Approved'  => "Appointment {$appt['appt_no']} approved.",
        'Rejected'  => "Appointment {$appt['appt_no']} rejected.",
        'Completed' => "Appointment {$appt['appt_no']} completed and consultation record saved.",
        'Cancelled' => "Appointment {$appt['appt_no']} cancelled.",
    ];
    flashMessage('appt_success', $msgs[$newStatus], 'success');
    redirectTo('/views/admin/appointments.php');

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error updating appointment {$apptId}: " . $e->getMessage());
    flashMessage('appt_error', 'A server error occurred. Please try again.', 'danger');
    redirectTo('/views/admin/appointments.php');
}

