<?php
require 'api/db.php';
header('Content-Type: application/json');

$stmt = $pdo->query('SELECT DISTINCT crop_name FROM crops ORDER BY crop_name');
$crops = $stmt->fetchAll(PDO::FETCH_COLUMN);
$data = array_map(function($name) { return ['crop_name' => $name]; }, $crops);

echo json_encode([
    'success' => true,
    'count' => count($data),
    'data' => $data
]);
?>
