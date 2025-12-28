<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$table = trim($_GET['table'] ?? '');

$allowedTables = ['weather_data', 'crops', 'users', 'admins', 'user_searches'];
if (!in_array($table, $allowedTables)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid table name']);
    exit;
}

function respondError(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            // List all records
            $limit = isset($_GET['limit']) ? min(1000, max(1, (int) $_GET['limit'])) : 100;
            $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
            
            // Build SELECT query - exclude created_at for tables other than users and admins
            $selectFields = '*';
            if ($table !== 'users' && $table !== 'admins') {
                // Get column names and exclude created_at
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $columns = array_filter($columns, function($col) {
                    return $col !== 'created_at';
                });
                if (!empty($columns)) {
                    $selectFields = '`' . implode('`, `', $columns) . '`';
                }
            }
            
            $stmt = $pdo->prepare("SELECT {$selectFields} FROM `{$table}` ORDER BY 1 DESC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
            break;

        case 'POST':
            // Create new record
            $fields = [];
            $values = [];
            $placeholders = [];
            foreach ($input as $key => $val) {
                if ($key !== 'id' && $key !== 'created_at' && $key !== 'last_login_at') {
                    $fields[] = "`{$key}`";
                    $placeholders[] = '?';
                    if ($key === 'password') {
                        $values[] = password_hash((string) $val, PASSWORD_DEFAULT);
                    } else {
                        $values[] = $val;
                    }
                }
            }
            if (empty($fields)) {
                respondError(400, 'No valid fields provided');
            }
            $sql = "INSERT INTO `{$table}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $newId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Record created']);
            break;

        case 'PUT':
            // Update record
            $id = $input['id'] ?? null;
            if (!$id) {
                respondError(400, 'ID is required for update');
            }
            $updates = [];
            $values = [];
            foreach ($input as $key => $val) {
                if ($key !== 'id' && $key !== 'created_at' && $key !== 'last_login_at') {
                    $updates[] = "`{$key}` = ?";
                    if ($key === 'password' && !empty($val)) {
                        $values[] = password_hash((string) $val, PASSWORD_DEFAULT);
                    } else {
                        $values[] = $val;
                    }
                }
            }
            if (empty($updates)) {
                respondError(400, 'No fields to update');
            }
            $idField = $table === 'users' ? 'user_id' : ($table === 'admins' ? 'admin_id' : ($table === 'crops' ? 'crop_id' : ($table === 'user_searches' ? 'search_id' : 'weather_id')));
            $values[] = $id;
            $sql = "UPDATE `{$table}` SET " . implode(', ', $updates) . " WHERE `{$idField}` = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            echo json_encode(['success' => true, 'message' => 'Record updated']);
            break;

        case 'DELETE':
            // Delete record
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                respondError(400, 'ID is required for delete');
            }
            $idField = $table === 'users' ? 'user_id' : ($table === 'admins' ? 'admin_id' : ($table === 'crops' ? 'crop_id' : ($table === 'user_searches' ? 'search_id' : 'weather_id')));
            $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `{$idField}` = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Record deleted']);
            break;

        default:
            respondError(405, 'Method not allowed');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

