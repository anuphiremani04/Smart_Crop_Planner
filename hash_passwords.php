<?php
require 'api/db.php';

// Hash all existing passwords in users table
$stmt = $pdo->query('SELECT user_id, password FROM users');
$users = $stmt->fetchAll();

foreach ($users as $user) {
    $hashed = password_hash($user['password'], PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    $updateStmt->execute([$hashed, $user['user_id']]);
    echo "Updated user {$user['user_id']}: {$user['password']} -> [hashed]\n";
}

// Hash all existing passwords in admins table
$stmt = $pdo->query('SELECT admin_id, password FROM admins');
$admins = $stmt->fetchAll();

foreach ($admins as $admin) {
    $hashed = password_hash($admin['password'], PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare('UPDATE admins SET password = ? WHERE admin_id = ?');
    $updateStmt->execute([$hashed, $admin['admin_id']]);
    echo "Updated admin {$admin['admin_id']}: {$admin['password']} -> [hashed]\n";
}

echo "\nAll passwords have been hashed successfully!\n";
?>
