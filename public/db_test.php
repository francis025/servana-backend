<?php
// Test database connection directly
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Load CodeIgniter's Database config
require_once __DIR__ . '/../app/Config/Database.php';

$dbConfig = new Config\Database();
$db = $dbConfig->default;

echo json_encode([
    'status' => 'testing',
    'database_config' => [
        'hostname' => $db['hostname'],
        'username' => $db['username'],
        'password' => substr($db['password'], 0, 3) . '***', // Show first 3 chars only
        'database' => $db['database'],
        'port' => $db['port'],
    ],
    'connection_test' => 'attempting...',
], JSON_PRETTY_PRINT);

// Try to connect
try {
    $mysqli = new mysqli($db['hostname'], $db['username'], $db['password'], $db['database'], $db['port']);
    
    if ($mysqli->connect_error) {
        echo "\n\nConnection FAILED: " . $mysqli->connect_error;
    } else {
        echo "\n\nConnection SUCCESS!";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "\n\nException: " . $e->getMessage();
}
