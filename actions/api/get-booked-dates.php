<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['booked_times' => []]);
    exit;
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT time FROM appointments
        WHERE date = ? AND status NOT IN ('Cancelled','Rejected')
    ");
    $stmt->execute([$date]);
    $times = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    // Normalize to HH:MM
    $times = array_map(fn($t) => substr($t, 0, 5), $times);
    echo json_encode(['booked_times' => $times]);
} catch (RuntimeException $e) {
    echo json_encode(['booked_times' => [], 'error' => 'Server error']);
}
