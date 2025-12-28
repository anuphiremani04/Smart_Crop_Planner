<?php
require 'api/db.php';
header('Content-Type: application/json');

// Test the recommendations API
$cropName = 'Rice';

$stmt = $pdo->prepare('SELECT crop_id, crop_name, suitable_soil, season, description, market_price_per_unit FROM crops WHERE crop_name = ? LIMIT 1');
$stmt->execute([$cropName]);
$crop = $stmt->fetch();

if (!$crop) {
    echo json_encode(['success' => false, 'error' => 'Crop not found']);
    exit;
}

// Get all weather data and format recommendations
$stmt = $pdo->query('SELECT DISTINCT location, season FROM weather_data ORDER BY location');
$locations = $stmt->fetchAll();

$recommendations = [];
$cropSeason = $crop['season'];

// Filter locations by matching season
foreach ($locations as $loc) {
    $loc_season = strtolower($loc['season']);
    $crop_season = strtolower($cropSeason);
    
    // Basic season matching
    if (strpos($crop_season, 'winter') !== false && strpos($loc_season, 'winter') !== false) {
        $recommendations[] = [
            'location' => $loc['location'],
            'season' => $loc['season'],
            'suitable_soil' => $crop['suitable_soil'],
            'crop_name' => $crop['crop_name'],
            'crop_description' => $crop['description'],
            'market_price' => $crop['market_price_per_unit']
        ];
    } elseif (strpos($crop_season, 'monsoon') !== false && strpos($loc_season, 'monsoon') !== false) {
        $recommendations[] = [
            'location' => $loc['location'],
            'season' => $loc['season'],
            'suitable_soil' => $crop['suitable_soil'],
            'crop_name' => $crop['crop_name'],
            'crop_description' => $crop['description'],
            'market_price' => $crop['market_price_per_unit']
        ];
    } elseif (strpos($crop_season, 'summer') !== false && strpos($loc_season, 'summer') !== false) {
        $recommendations[] = [
            'location' => $loc['location'],
            'season' => $loc['season'],
            'suitable_soil' => $crop['suitable_soil'],
            'crop_name' => $crop['crop_name'],
            'crop_description' => $crop['description'],
            'market_price' => $crop['market_price_per_unit']
        ];
    }
}

// If no exact season match, return top locations anyway
if (count($recommendations) === 0) {
    foreach (array_slice($locations, 0, 10) as $loc) {
        $recommendations[] = [
            'location' => $loc['location'],
            'season' => $loc['season'],
            'suitable_soil' => $crop['suitable_soil'],
            'crop_name' => $crop['crop_name'],
            'crop_description' => $crop['description'],
            'market_price' => $crop['market_price_per_unit']
        ];
    }
}

echo json_encode([
    'success' => true,
    'crop' => $crop,
    'total_locations' => count($locations),
    'matched_recommendations' => count($recommendations),
    'recommendations' => array_slice($recommendations, 0, 5)
], JSON_PRETTY_PRINT);
?>
