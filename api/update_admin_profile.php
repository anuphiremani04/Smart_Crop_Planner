<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function parseInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos((string) $contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input') ?: '';
    parse_str($raw, $data);
    return is_array($data) ? $data : [];
}

$input = parseInput();
$admin_id = (int) ($input['admin_id'] ?? 0);

if (!$admin_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Admin ID is required']);
    exit;
}

$username = trim((string) ($input['username'] ?? ''));
$currentPassword = trim((string) ($input['current_password'] ?? ''));
$newPassword = trim((string) ($input['new_password'] ?? ''));

if (!$username) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username is required']);
    exit;
}

// Check if username already exists for another admin
$stmt = $pdo->prepare('SELECT admin_id FROM admins WHERE username = ? AND admin_id != ? LIMIT 1');
$stmt->execute([$username, $admin_id]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username already exists']);
    exit;
}

$updates = [];
$values = [];

$updates[] = 'username = ?';
$values[] = $username;

// Handle password change
if ($currentPassword && $newPassword) {
    // Verify current password
    $stmt = $pdo->prepare('SELECT password FROM admins WHERE admin_id = ? LIMIT 1');
    $stmt->execute([$admin_id]);
    $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminData || !password_verify($currentPassword, $adminData['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        exit;
    }
    
    // Validate new password length
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long']);
        exit;
    }
    
    // Hash and update password
    $updates[] = 'password = ?';
    $values[] = password_hash($newPassword, PASSWORD_DEFAULT);
}

$values[] = $admin_id;
$sql = 'UPDATE admins SET ' . implode(', ', $updates) . ' WHERE admin_id = ?';
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute($values);
    // Fetch updated admin data
    $stmt = $pdo->prepare('SELECT admin_id, username, role FROM admins WHERE admin_id = ? LIMIT 1');
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'admin' => $admin, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()]);
}
?>

