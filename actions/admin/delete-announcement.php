<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/admin/announcements.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    flashMessage('announcement_error', 'Invalid announcement.', 'danger');
    redirectTo('/views/admin/announcements.php');
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    flashMessage('announcement_success', 'Announcement deleted successfully.', 'success');
} catch (Exception $e) {
    flashMessage('announcement_error', 'Database error: ' . $e->getMessage(), 'danger');
}

redirectTo('/views/admin/announcements.php');
