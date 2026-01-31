<?php
// Direct test of the login page without CodeIgniter routing
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap CodeIgniter
$app = require_once __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require realpath($bootstrap) ?: $bootstrap;

// Create application
$app = Config\Services::codeigniter();
$app->initialize();

// Try to access the Auth controller
try {
    $request = \Config\Services::request();
    $request->setMethod('get');
    
    // Load the Auth controller
    $controller = new \App\Controllers\Auth();
    
    echo "Testing Auth controller login method...\n\n";
    
    // Try to call login
    ob_start();
    $result = $controller->login();
    $output = ob_get_clean();
    
    if ($result) {
        echo "✓ Login method executed successfully\n";
        echo "Output length: " . strlen($output) . " bytes\n";
    } else {
        echo "✗ Login method returned false/null\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Exception caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "✗ Error caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
