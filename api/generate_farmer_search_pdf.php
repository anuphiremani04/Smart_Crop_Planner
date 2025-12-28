<?php
require_once __DIR__ . '/../lib/SimplePDF.php';
require_once __DIR__ . '/db.php';

// Get parameters from POST
$crop_name = trim((string) ($_POST['crop_name'] ?? ''));
$user_id = (int) ($_POST['user_id'] ?? 0);

if (!$crop_name) {
    die('Invalid search parameters');
}

// Get user info
$userName = 'Farmer';
if ($user_id > 0) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userName = $user['name'];
    }
}

// Get crop details
$stmt = $pdo->prepare('SELECT crop_id, crop_name, suitable_soil, season, description, market_price_per_unit FROM crops WHERE crop_name = ? LIMIT 1');
$stmt->execute([$crop_name]);
$crop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$crop) {
    die('Crop not found');
}

// Get suitable locations based on season
$stmt = $pdo->prepare('SELECT location, season, AVG(temperature) as temperature, AVG(rainfall) as rainfall, AVG(humidity) as humidity FROM weather_data WHERE season = ? GROUP BY location, season ORDER BY location ASC LIMIT 30');
$stmt->execute([$crop['season']]);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recommendations)) {
    die('No suitable locations found');
}

// Generate PDF
try {
    $pdf = new SimplePDF('Crop Recommendations');
    
    // Simple title
    $pdf->addLine('SMART CROP PLANNER', 18, true);
    $pdf->addLine('Crop Recommendations', 14, false);
    $pdf->addSpacer(10);
    
    // Basic info
    $pdf->addLine('Farmer: ' . htmlspecialchars($userName), 10, false);
    $pdf->addLine('Date: ' . date('d-M-Y H:i'), 10, false);
    $pdf->addSpacer(8);
    
    // Crop details section
    $pdf->addLine('CROP DETAILS', 12, true);
    $pdf->addSpacer(3);
    
    // Create a simple table for crop info
    $cropInfoHeaders = ['Property', 'Details'];
    $cropInfoRows = [
        ['Crop Name', htmlspecialchars($crop['crop_name'])],
        ['Season', htmlspecialchars($crop['season'])],
        ['Soil Type', htmlspecialchars($crop['suitable_soil'])],
        ['Description', htmlspecialchars($crop['description'])],
        ['Market Price', '₹' . number_format((float)$crop['market_price_per_unit'], 2)]
    ];
    
    $pdf->addTable($cropInfoHeaders, $cropInfoRows, 10, false);
    $pdf->addSpacer(10);
    
    // Recommendations section
    $pdf->addLine('SUITABLE LOCATIONS FOR GROWING', 12, true);
    $pdf->addSpacer(3);
    
    $tableHeaders = ['#', 'Location', 'Temp (°C)', 'Rain (mm)', 'Humidity (%)'];
    $tableRows = [];
    
    foreach ($recommendations as $index => $loc) {
        $tableRows[] = [
            $index + 1,
            htmlspecialchars($loc['location']),
            number_format((float)$loc['temperature'], 1),
            number_format((float)$loc['rainfall'], 1),
            number_format((float)$loc['humidity'], 1)
        ];
    }
    
    $pdf->addTable($tableHeaders, $tableRows, 9, false);
    $pdf->addSpacer(8);
    
    // Summary footer
    $pdf->addLine('Total Suitable Locations Found: ' . count($recommendations), 10, true);
    $pdf->addSpacer(5);
    $pdf->addLine('Generated on ' . date('Y-m-d H:i:s'), 8, false);
    
    // Output PDF
    $filename = 'crop_recommendations_' . date('Ymd_His') . '.pdf';
    $pdf->output($filename, true);
    
} catch (Exception $e) {
    http_response_code(500);
    die('PDF Generation Error: ' . $e->getMessage());
}
?>

