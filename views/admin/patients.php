<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');
header('Location: ' . BASE_URL . '/views/admin/dashboard.php');
exit;

