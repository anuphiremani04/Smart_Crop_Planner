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
$username = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
$role = 'data_entry'; // Default role for new admins

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

$stmt = $pdo->prepare('SELECT admin_id FROM admins WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Admin username already exists']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO admins (username, password, role) VALUES (?, ?, ?)');
try {
    $stmt->execute([$username, $hash, $role]);
    echo json_encode(['success' => true, 'admin_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()]);
}
?>