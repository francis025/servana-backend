<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$password = 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
$database = 'railway';
$port = 48486;

echo "=== CHECKING SETTINGS TABLE STRUCTURE ===\n\n";

try {
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        die("❌ CONNECTION FAILED: " . $mysqli->connect_error . "\n");
    }
    
    echo "✅ Connected!\n\n";
    
    // Show table structure
    $result = $mysqli->query("DESCRIBE settings");
    
    echo "Settings table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
