<?php
// Mock Environment for Slim
$_SERVER['REQUEST_URI'] = '/MCAG_Militare-Civile-Archivio-Gestionale/public/api/public/security/pulse?reset_geo=1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/MCAG_Militare-Civile-Archivio-Gestionale/public/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['reset_geo'] = '1';

// Capture output
ob_start();

// Load App
require __DIR__ . '/public/index.php';

$output = ob_get_clean();

echo "--- OUTPUT START ---\n";
echo $output;
echo "\n--- OUTPUT END ---\n";
