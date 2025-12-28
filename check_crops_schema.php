<?php
require 'api/db.php';
header('Content-Type: application/json');

// First, get the table structure
$stmt = $pdo->query("DESCRIBE crops");
$columns = $stmt->fetchAll();

echo "Columns in crops table:\n";
foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\nFirst 3 crops:\n";
$stmt = $pdo->query("SELECT * FROM crops LIMIT 3");
$crops = $stmt->fetchAll();
echo json_encode($crops, JSON_PRETTY_PRINT);
?>
