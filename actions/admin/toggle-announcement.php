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
    $stmt = $pdo->prepare("SELECT is_active FROM announcements WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        flashMessage('announcement_error', 'Announcement not found.', 'danger');
        redirectTo('/views/admin/announcements.php');
    }

    $newActive = $row['is_active'] ? 0 : 1;
    $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?")->execute([$newActive, $id]);

    $label = $newActive ? 'activated and is now visible to patients' : 'deactivated';
    flashMessage('announcement_success', "Announcement {$label}.", 'success');
} catch (Exception $e) {
    flashMessage('announcement_error', 'Database error: ' . $e->getMessage(), 'danger');
}

redirectTo('/views/admin/announcements.php');
