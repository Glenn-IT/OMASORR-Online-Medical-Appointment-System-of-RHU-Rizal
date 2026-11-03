<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/announcements.php');
}
verifyCsrf();

$admin   = getAdminSession();
$adminId = (int)$admin['id'];

$id        = (int)($_POST['id'] ?? 0);
$title     = trim($_POST['title'] ?? '');
$message   = trim($_POST['message'] ?? '');
$type      = trim($_POST['type'] ?? 'general_advisory');
$doctorId  = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$serviceId = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
$startDate = !empty($_POST['start_date']) ? trim($_POST['start_date']) : null;
$endDate   = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
$isActive  = isset($_POST['is_active']) ? 1 : 0;

$validTypes = ['doctor_unavailable', 'service_unavailable', 'general_advisory'];
if (!in_array($type, $validTypes, true)) {
    $type = 'general_advisory';
}

if (!$title || !$message) {
    flashMessage('announcement_error', 'Title and message are required.', 'danger');
    redirectTo('/views/admin/announcements.php');
}

try {
    $pdo = db();
    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE announcements 
            SET title = ?, message = ?, type = ?, doctor_id = ?, service_id = ?, 
                start_date = ?, end_date = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $message, $type, $doctorId, $serviceId, $startDate, $endDate, $isActive, $id]);
        flashMessage('announcement_success', 'Announcement updated successfully.', 'success');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (title, message, type, doctor_id, service_id, start_date, end_date, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $message, $type, $doctorId, $serviceId, $startDate, $endDate, $isActive, $adminId]);
        flashMessage('announcement_success', 'Announcement published successfully.', 'success');
    }
} catch (Exception $e) {
    flashMessage('announcement_error', 'Database error: ' . $e->getMessage(), 'danger');
}

redirectTo('/views/admin/announcements.php');
