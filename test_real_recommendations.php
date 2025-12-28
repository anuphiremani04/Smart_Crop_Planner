<?php
require 'api/db.php';
header('Content-Type: application/json');

$cropName = 'Rice';

$stmt = $pdo->prepare('SELECT crop_id, crop_name, suitable_soil, season, description, market_price_per_unit FROM crops WHERE crop_name = ? LIMIT 1');
$stmt->execute([$cropName]);
$crop = $stmt->fetch();

if (!$crop) {
    echo json_encode(['success' => false, 'error' => 'Crop not found']);
    exit;
}

// Get all weather data with averages per location-season
$stmt = $pdo->query('SELECT location, season, AVG(temperature) as temperature, AVG(rainfall) as rainfall, AVG(humidity) as humidity FROM weather_data GROUP BY location, season ORDER BY location');
$locations = $stmt->fetchAll();

$recommendations = [];
$cropSeason = $crop['season'];

// Filter locations by matching season
foreach ($locations as $loc) {
    $loc_season = strtolower($loc['season']);
    $crop_season = strtolower($cropSeason);
    
    // Basic season matching
    if (strpos($crop_season, 'monsoon') !== false && strpos($loc_season, 'monsoon') !== false) {
        $recommendations[] = [
            'location' => $loc['location'],
            'season' => $loc['season'],
            'temperature' => round($loc['temperature'], 2),
            'rainfall' => round($loc['rainfall'], 2),
            'humidity' => round($loc['humidity'], 2),
            'suitable_soil' => $crop['suitable_soil'],
            'crop_name' => $crop['crop_name'],
            'crop_description' => $crop['description'],
            'market_price' => $crop['market_price_per_unit']
        ];
    }
}

echo json_encode([
    'success' => true,
    'total_locations' => count($locations),
    'matched_recommendations' => count($recommendations),
    'recommendations' => array_slice($recommendations, 0, 5)
], JSON_PRETTY_PRINT);
?>
