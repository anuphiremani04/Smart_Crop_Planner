<?php
require 'api/db.php';
header('Content-Type: application/json');

$stmt = $pdo->query('SELECT crop_id, crop_name, suitable_soil, season, description, market_price_per_unit FROM crops ORDER BY crop_name LIMIT 200');
$data = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'count' => count($data),
    'data' => $data
]);
?>
