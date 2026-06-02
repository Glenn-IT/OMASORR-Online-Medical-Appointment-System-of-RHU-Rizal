<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/views/user/signup.php');
}
verifyCsrf();

// --- Collect & sanitize ---
$firstName  = trim($_POST['first_name']  ?? '');
$lastName   = trim($_POST['last_name']   ?? '');
$birthdate  = trim($_POST['birthdate']   ?? '');
$gender     = trim($_POST['gender']      ?? '');
$phone      = trim($_POST['phone']       ?? '');
$address    = trim($_POST['address']     ?? '');
$bloodType  = trim($_POST['blood_type']  ?? '');
$email      = trim($_POST['email']       ?? '');
$username   = trim($_POST['username']    ?? '');
$password   = $_POST['password']         ?? '';
$confirm    = $_POST['confirm_password'] ?? '';

// --- Server-side validation ---
$errors = [];

if (!$firstName || !$lastName)          $errors[] = 'First name and last name are required.';
if (!$birthdate)                         $errors[] = 'Date of birth is required.';
if (!in_array($gender, ['Male','Female','Other'])) $errors[] = 'Please select a valid gender.';
if (!$phone)                             $errors[] = 'Phone number is required.';
elseif (!preg_match('/^09\d{9}$/', $phone)) $errors[] = 'Phone number must be 11 digits starting with 09 (e.g. 09123456789).';
if (!$address)                           $errors[] = 'Address is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (strlen($username) < 4 || strpos($username, ' ') !== false) $errors[] = 'Username must be at least 4 characters and contain no spaces.';
if (strlen($password) < 8)              $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm)             $errors[] = 'Passwords do not match.';

if ($errors) {
    flashMessage('signup_error', implode(' ', $errors), 'danger');
    redirectTo('/views/user/signup.php');
}

try {
    $pdo = db();

    // Check username uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        flashMessage('signup_error', 'That username is already taken. Please choose another.', 'danger');
        redirectTo('/views/user/signup.php');
    }

    // Check email uniqueness
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flashMessage('signup_error', 'An account with that email address already exists.', 'danger');
        redirectTo('/views/user/signup.php');
    }

    $pdo->beginTransaction();

    // Insert into users
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'patient', 'Active')");
    $stmt->execute([$username, $hash]);
    $userId = (int) $pdo->lastInsertId();

    // Generate patient_no: P-001, P-002, …
    $cnt = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $patientNo = 'P-' . str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);

    // Insert into patients
    $stmt = $pdo->prepare("
        INSERT INTO patients (user_id, patient_no, full_name, email, phone, address, birthdate, gender, blood_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $patientNo,
        trim("$firstName $lastName"),
        $email,
        $phone,
        $address,
        $birthdate,
        $gender,
        $bloodType ?: null,
    ]);

    $pdo->commit();

    flashMessage('signup_success', 'Account created successfully! You can now log in.', 'success');
    redirectTo('/index.php');

} catch (RuntimeException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    flashMessage('signup_error', 'A server error occurred. Please try again later.', 'danger');
    redirectTo('/views/user/signup.php');
} catch (\PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    flashMessage('signup_error', 'A database error occurred. Please try again later.', 'danger');
    redirectTo('/views/user/signup.php');
}
