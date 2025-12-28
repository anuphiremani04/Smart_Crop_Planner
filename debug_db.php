<?php
require 'api/db.php';

// Test 1: Check if database connection works
echo "=== DATABASE CONNECTION TEST ===\n";
try {
    $stmt = $pdo->query('SELECT 1');
    echo "✓ Database connection successful\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Check users table
echo "=== USERS TABLE ===\n";
$stmt = $pdo->query('SELECT user_id, username, password FROM users');
$users = $stmt->fetchAll();
foreach ($users as $user) {
    echo "User ID {$user['user_id']}: {$user['username']}\n";
    echo "  Password hash: " . substr($user['password'], 0, 20) . "...\n";
    echo "  Is hash? " . (strpos($user['password'], '$2y$') === 0 ? "YES" : "NO") . "\n";
}

// Test 3: Check admins table  
echo "\n=== ADMINS TABLE ===\n";
$stmt = $pdo->query('SELECT admin_id, username, password FROM admins');
$admins = $stmt->fetchAll();
foreach ($admins as $admin) {
    echo "Admin ID {$admin['admin_id']}: {$admin['username']}\n";
    echo "  Password hash: " . substr($admin['password'], 0, 20) . "...\n";
    echo "  Is hash? " . (strpos($admin['password'], '$2y$') === 0 ? "YES" : "NO") . "\n";
}

// Test 4: Test password_verify with known values
echo "\n=== PASSWORD VERIFICATION TEST ===\n";
echo "Testing farmer1 with password 'password':\n";
if ($users) {
    $test_result = password_verify('password', $users[0]['password']);
    echo "Result: " . ($test_result ? "✓ PASS" : "✗ FAIL") . "\n";
}

echo "\nTesting admin with password 'admin':\n";
if ($admins) {
    $test_result = password_verify('admin', $admins[0]['password']);
    echo "Result: " . ($test_result ? "✓ PASS" : "✗ FAIL") . "\n";
}
?>
