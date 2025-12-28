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
$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? $input['username'] ?? ''));
$username = trim((string) ($input['username'] ?? $email));
$password = (string) ($input['password'] ?? '');
$contact = trim((string) ($input['contact'] ?? $input['contact_no'] ?? ''));
$location = trim((string) ($input['location'] ?? ''));
$soil = trim((string) ($input['soil'] ?? $input['soil_type'] ?? ''));

if (!$name || !$username || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: name, username/email, and password']);
    exit;
}

$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username already exists']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (name, location, soil_type, username, password, contact_no) VALUES (?, ?, ?, ?, ?, ?)');
try {
    $stmt->execute([$name, $location, $soil, $username, $hash, $contact]);
    echo json_encode(['success' => true, 'user_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()]);
}
?>