<?php
require 'config/database.php';
$pdo = db();
echo "=== users ===\n";
foreach ($pdo->query('DESCRIBE users')->fetchAll() as $c) {
    echo $c['Field'] . ' ' . $c['Type'] . "\n";
}
echo "=== patients ===\n";
foreach ($pdo->query('DESCRIBE patients')->fetchAll() as $c) {
    echo $c['Field'] . ' ' . $c['Type'] . "\n";
}
