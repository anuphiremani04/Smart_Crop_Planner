<?php
require 'api/db.php';

// Check for duplicate crop names
$stmt = $pdo->query('SELECT crop_name, COUNT(*) as count FROM crops GROUP BY crop_name HAVING count > 1 ORDER BY count DESC');
$duplicates = $stmt->fetchAll();

echo "Duplicate Crops:\n";
echo "===============\n\n";

foreach ($duplicates as $dup) {
    echo $dup['crop_name'] . " - appears " . $dup['count'] . " times\n";
    
    // Show all IDs for this crop
    $stmt2 = $pdo->prepare('SELECT crop_id FROM crops WHERE crop_name = ? ORDER BY crop_id');
    $stmt2->execute([$dup['crop_name']]);
    $ids = $stmt2->fetchAll();
    echo "  IDs: " . implode(', ', array_column($ids, 'crop_id')) . "\n\n";
}

$total_stmt = $pdo->query('SELECT COUNT(*) as total FROM crops');
$total = $total_stmt->fetch()['total'];

$unique_stmt = $pdo->query('SELECT COUNT(DISTINCT crop_name) as unique_count FROM crops');
$unique = $unique_stmt->fetch()['unique_count'];

echo "\nSummary:\n";
echo "Total crops in DB: " . $total . "\n";
echo "Unique crop names: " . $unique . "\n";
?>
