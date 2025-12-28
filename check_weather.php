<?php
require 'api/db.php';
$stmt = $pdo->query('SELECT * FROM weather_data LIMIT 1');
$record = $stmt->fetch();
if ($record) {
    foreach ($record as $key => $value) {
        echo $key . ": " . $value . "\n";
    }
} else {
    echo "No weather data found\n";
}
?>
