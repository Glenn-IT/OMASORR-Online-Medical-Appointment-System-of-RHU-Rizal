<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/index.php');
}
verifyCsrf();

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    flashMessage('login_error', 'Please enter your username and password.', 'warning');
    redirectTo('/index.php');
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, u.username, u.password AS password_hash, u.status,
               p.id AS patient_id, p.patient_no, p.full_name, p.birthdate, p.gender, p.phone
        FROM users u
        JOIN patients p ON p.user_id = u.id
        WHERE u.username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        flashMessage('login_error', 'Invalid username or password.', 'danger');
        redirectTo('/index.php');
    }

    if ($row['status'] !== 'Active') {
        flashMessage('login_error', 'Your account is inactive. Please contact the clinic.', 'warning');
        redirectTo('/index.php');
    }

    $user    = ['username' => $row['username'], 'status' => $row['status']];
    $patient = [
        'id'         => $row['patient_id'],
        'patient_no' => $row['patient_no'],
        'full_name'  => $row['full_name'],
    ];

    setPatientSession($patient, $user);
    redirectTo('/views/user/dashboard.php');

} catch (RuntimeException $e) {
    flashMessage('login_error', 'A server error occurred. Please try again later.', 'danger');
    redirectTo('/index.php');
}
