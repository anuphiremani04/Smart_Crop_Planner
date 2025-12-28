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
$user_id = (int) ($input['user_id'] ?? 0);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$username = trim((string) ($input['username'] ?? ''));
$contact = trim((string) ($input['contact'] ?? ''));
$location = trim((string) ($input['location'] ?? ''));
$soil = trim((string) ($input['soil'] ?? ''));
$currentPassword = trim((string) ($input['current_password'] ?? ''));
$newPassword = trim((string) ($input['new_password'] ?? ''));

$updates = [];
$values = [];

if ($name) {
    $updates[] = 'name = ?';
    $values[] = $name;
}
if ($email) {
    $updates[] = 'email = ?';
    $values[] = $email;
}
if ($username) {
    // Check if username already exists for another user
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1');
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
        exit;
    }
    $updates[] = 'username = ?';
    $values[] = $username;
}
if ($contact !== '') {
    $updates[] = 'contact_no = ?';
    $values[] = $contact;
}
if ($location !== '') {
    $updates[] = 'location = ?';
    $values[] = $location;
}
if ($soil !== '') {
    $updates[] = 'soil_type = ?';
    $values[] = $soil;
}

// Handle password change
if ($currentPassword && $newPassword) {
    // Verify current password
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
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

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

$values[] = $user_id;
$sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE user_id = ?';
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute($values);
    // Fetch updated user data
    $stmt = $pdo->prepare('SELECT user_id, name, username, email, contact_no, location, soil_type FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'user' => $user, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()]);
}
?>

