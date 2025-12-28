<?php
require 'api/db.php';

// Check weather_data table structure and content
$stmt = $pdo->query('DESCRIBE weather_data');
$columns = $stmt->fetchAll();

echo "Weather Data Table Columns:\n";
foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n\nFirst 5 weather records:\n";
$stmt = $pdo->query('SELECT * FROM weather_data LIMIT 5');
$records = $stmt->fetchAll();
echo json_encode($records, JSON_PRETTY_PRINT);

echo "\n\nSample data for Anekal:\n";
$stmt = $pdo->query('SELECT * FROM weather_data WHERE location = "Anekal" LIMIT 3');
$records = $stmt->fetchAll();
echo json_encode($records, JSON_PRETTY_PRINT);
?>
