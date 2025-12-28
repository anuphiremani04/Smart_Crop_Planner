<?php
require 'api/db.php';
header('Content-Type: application/json');

// Simulate farmer login with farmer1 / password
$username = 'farmer1';
$password = 'password';

$stmt = $pdo->prepare('SELECT user_id, name, username, password FROM users WHERE username=? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found', 'username' => $username]);
    exit;
}

$pass_check = password_verify($password, $user['password']);

echo json_encode([
    'success' => $pass_check,
    'username' => $username,
    'stored_hash_start' => substr($user['password'], 0, 30),
    'password_verify_result' => $pass_check,
    'user_id' => $user['user_id'],
    'user_name' => $user['name']
]);
?>
