<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$limit = isset($_GET['limit']) ? min(10, max(5, (int) $_GET['limit'])) : 8;

// Get a larger pool of top crops (top 30-40 by different criteria)
// Then randomly select from them to show variety on each refresh

// Strategy: Get top crops by different criteria and combine, then randomize
$allCrops = [];

// Get top crops by market price
$stmt = $pdo->prepare('
    SELECT crop_id, crop_name, suitable_soil, min_temperature, max_temperature, 
           min_rainfall, max_rainfall, season, description, market_price_per_unit, 
           avg_yield_per_hectare
    FROM crops 
    WHERE market_price_per_unit IS NOT NULL
    ORDER BY market_price_per_unit DESC 
    LIMIT 15
');
$stmt->execute();
$byPrice = $stmt->fetchAll();
$allCrops = array_merge($allCrops, $byPrice);

// Get top crops by yield
$stmt = $pdo->prepare('
    SELECT crop_id, crop_name, suitable_soil, min_temperature, max_temperature, 
           min_rainfall, max_rainfall, season, description, market_price_per_unit, 
           avg_yield_per_hectare
    FROM crops 
    WHERE avg_yield_per_hectare IS NOT NULL
    ORDER BY avg_yield_per_hectare DESC 
    LIMIT 15
');
$stmt->execute();
$byYield = $stmt->fetchAll();
$allCrops = array_merge($allCrops, $byYield);

// Get random crops for variety
$stmt = $pdo->prepare('
    SELECT crop_id, crop_name, suitable_soil, min_temperature, max_temperature, 
           min_rainfall, max_rainfall, season, description, market_price_per_unit, 
           avg_yield_per_hectare
    FROM crops 
    ORDER BY RAND()
    LIMIT 15
');
$stmt->execute();
$byRandom = $stmt->fetchAll();
$allCrops = array_merge($allCrops, $byRandom);

// Remove duplicates based on crop_id
$uniqueCrops = [];
$seenIds = [];
foreach ($allCrops as $crop) {
    $id = $crop['crop_id'];
    if (!isset($seenIds[$id])) {
        $uniqueCrops[] = $crop;
        $seenIds[$id] = true;
    }
}

// Shuffle the array to randomize
shuffle($uniqueCrops);

// Take the requested limit
$crops = array_slice($uniqueCrops, 0, $limit);

// Re-sort by market price for display
usort($crops, function($a, $b) {
    $priceA = (float)($a['market_price_per_unit'] ?? 0);
    $priceB = (float)($b['market_price_per_unit'] ?? 0);
    return $priceB <=> $priceA;
});

echo json_encode(['success' => true, 'crops' => $crops]);
?>

