<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

$date     = $_GET['date'] ?? '';
$doctorId = (int) ($_GET['doctor_id'] ?? 0);

if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['booked_times' => []]);
    exit;
}

try {
    $pdo = db();
    if ($doctorId > 0) {
        $stmt = $pdo->prepare("
            SELECT time FROM appointments
            WHERE date = ? AND doctor_id = ? AND status NOT IN ('Cancelled','Rejected')
        ");
        $stmt->execute([$date, $doctorId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT time FROM appointments
            WHERE date = ? AND status NOT IN ('Cancelled','Rejected')
        ");
        $stmt->execute([$date]);
    }
    $times = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    // Normalize to HH:MM
    $times = array_map(fn($t) => substr($t, 0, 5), $times);

    // If querying today, calculate elapsed slots
    $pastTimes = [];
    if ($date === date('Y-m-d')) {
        $currentHM = date('H:i');
        $allSlots = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00'];
        foreach ($allSlots as $slot) {
            if ($slot <= $currentHM) {
                $pastTimes[] = $slot;
            }
        }
    }

    echo json_encode([
        'booked_times' => $times,
        'past_times'   => $pastTimes,
        'is_today'     => ($date === date('Y-m-d')),
        'server_time'  => date('H:i')
    ]);
} catch (RuntimeException $e) {
    echo json_encode(['booked_times' => [], 'past_times' => [], 'error' => 'Server error']);
}
