<?php
// Simulate the API request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create mock input
$input = json_encode(['crop_name' => 'Rice']);

// Temporarily override file_get_contents for testing
$GLOBALS['test_input'] = $input;

// Include and run the API
ob_start();
require 'api/get_recommendations.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";
?>
