<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin('admin');

$fMonth   = $_GET['month']   ?? date('m');
$fStatus  = $_GET['status']  ?? '';
$fService = $_GET['service'] ?? '';

$where  = ['1=1'];
$params = [];
if ($fMonth) {
    $where[] = 'MONTH(a.date) = :month';
    $params[':month'] = (int)$fMonth;
}
if ($fStatus) {
    $where[] = 'a.status = :status';
    $params[':status'] = $fStatus;
}
if ($fService) {
    $where[] = 'a.service = :service';
    $params[':service'] = $fService;
}

$sql = 'SELECT a.appt_no, p.full_name AS patient_name, a.service,
               COALESCE(d.name,"N/A") AS doctor_name, a.date, a.time, a.status
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        LEFT JOIN doctors d ON d.id = a.doctor_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY a.date DESC, a.time DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = 'appointments-report-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Appt. No.', 'Patient Name', 'Service', 'Doctor', 'Date', 'Time', 'Status']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['appt_no'],
        $r['patient_name'],
        $r['service'],
        $r['doctor_name'],
        $r['date'],
        $r['time'],
        $r['status'],
    ]);
}
fclose($out);
exit;
