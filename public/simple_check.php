<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SIMPLE CHECK ===\n\n";

$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$password = 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
$database = 'railway';
$port = 48486;

try {
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    if ($mysqli->connect_error) {
        die("❌ DB failed: " . $mysqli->connect_error);
    }
    echo "✅ Database connected\n\n";
    
    // Check each table individually
    $tables = ['users', 'settings', 'groups', 'users_groups'];
    foreach ($tables as $table) {
        try {
            $result = $mysqli->query("SELECT COUNT(*) as count FROM $table");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "✅ $table: {$row['count']} rows\n";
            } else {
                echo "❌ $table: Query failed - " . $mysqli->error . "\n";
            }
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== All Settings ===\n";
    $result = $mysqli->query("SELECT variable FROM settings ORDER BY variable");
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['variable'] . "\n";
    }
    
    $mysqli->close();
    echo "\n✅ DONE\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
