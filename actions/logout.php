<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

$wasAdmin = isLoggedIn('admin');

// Fully destroy the session
session_unset();
session_destroy();

// Expire the session cookie immediately
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(SESSION_NAME),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Prevent caching of this response
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if ($wasAdmin) {
    header('Location: ' . BASE_URL . '/views/admin/login.php');
} else {
    header('Location: ' . BASE_URL . '/index.php');
}
exit;
