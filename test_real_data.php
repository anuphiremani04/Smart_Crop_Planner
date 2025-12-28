<?php
require 'api/db.php';

// Get real weather data for a specific location
$stmt = $pdo->query('SELECT * FROM weather_data WHERE location = "Bengaluru" LIMIT 5');
$records = $stmt->fetchAll();

echo "Real weather data for Bengaluru:\n";
echo json_encode($records, JSON_PRETTY_PRINT) . "\n\n";

// Get sample recommendations
$stmt = $pdo->prepare('SELECT crop_id, crop_name, suitable_soil, season FROM crops WHERE crop_name = ? LIMIT 1');
$stmt->execute(['Rice']);
$crop = $stmt->fetch();

echo "Crop: " . json_encode($crop, JSON_PRETTY_PRINT) . "\n\n";

// Get weather data with actual values
$stmt = $pdo->query('SELECT DISTINCT location, season, temperature, rainfall, humidity FROM weather_data WHERE season = "Monsoon" ORDER BY location LIMIT 5');
$locations = $stmt->fetchAll();

echo "Real weather data (Monsoon season):\n";
echo json_encode($locations, JSON_PRETTY_PRINT);
?>
