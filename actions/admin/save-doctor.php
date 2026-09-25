<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/doctors.php');
}
verifyCsrf();

$id        = (int)trim($_POST['id']        ?? 0);
$name      = trim($_POST['name']           ?? '');
$specialty = trim($_POST['specialty']      ?? '');
$schedule  = trim($_POST['schedule']       ?? '');
$available = isset($_POST['available']) ? (int)(bool)$_POST['available'] : 1;

// Fallback: If schedule string is empty but schedule_days array was submitted
if (!$schedule && !empty($_POST['schedule_days']) && is_array($_POST['schedule_days'])) {
    $validDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $selectedDays = array_values(array_intersect($validDays, $_POST['schedule_days']));
    if (!empty($selectedDays)) {
        if ($selectedDays === ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']) {
            $schedule = 'Mon-Fri';
        } elseif ($selectedDays === ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']) {
            $schedule = 'Mon-Sat';
        } elseif ($selectedDays === ['Mon', 'Tue', 'Wed', 'Thu']) {
            $schedule = 'Mon-Thu';
        } elseif ($selectedDays === ['Tue', 'Wed', 'Thu', 'Fri']) {
            $schedule = 'Tue-Fri';
        } elseif ($selectedDays === ['Wed', 'Fri']) {
            $schedule = 'Wed-Fri';
        } elseif ($selectedDays === ['Tue', 'Thu']) {
            $schedule = 'Tue-Thu';
        } elseif ($selectedDays === ['Mon', 'Wed', 'Fri']) {
            $schedule = 'Mon-Wed-Fri';
        } else {
            $schedule = implode('-', $selectedDays);
        }
    }
}

if (!$name || !$specialty || !$schedule) {
    flashMessage('doctor_error', 'Please fill in all required fields and select at least one clinic duty day.', 'danger');
    redirectTo('/views/admin/doctors.php');
}

try {
    $pdo = db();

    if ($id > 0) {
        // Edit existing
        $pdo->prepare("UPDATE doctors SET name=?, specialty=?, schedule=?, available=? WHERE id=?")
            ->execute([$name, $specialty, $schedule, $available, $id]);
        flashMessage('doctor_success', 'Doctor updated successfully.', 'success');
    } else {
        // Add new
        $pdo->prepare("INSERT INTO doctors (name, specialty, schedule, available) VALUES (?, ?, ?, ?)")
            ->execute([$name, $specialty, $schedule, $available]);
        flashMessage('doctor_success', 'Doctor added successfully.', 'success');
    }
} catch (Exception $e) {
    flashMessage('doctor_error', 'Database error: ' . $e->getMessage(), 'danger');
}

redirectTo('/views/admin/doctors.php');
?>
