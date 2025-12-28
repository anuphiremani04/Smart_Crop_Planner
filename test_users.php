<?php
require 'api/db.php';

// Check users table
$stmt = $pdo->query('SELECT * FROM users LIMIT 5');
$users = $stmt->fetchAll();

echo "Users in database:\n";
echo json_encode($users, JSON_PRETTY_PRINT) . "\n\n";

// Check admins table
$stmt = $pdo->query('SELECT admin_id, username, role FROM admins LIMIT 5');
$admins = $stmt->fetchAll();

echo "Admins in database:\n";
echo json_encode($admins, JSON_PRETTY_PRINT) . "\n";
?>
