<?php
require 'config/database.php';
$row = db()->query('SELECT id, username, password FROM admin_users LIMIT 1')->fetch(PDO::FETCH_ASSOC);
var_dump($row);
