<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

// Direct database connection test
$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$password = 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
$database = 'railway';
$port = 48486;

echo "=== DATABASE CONNECTION TEST ===\n\n";

try {
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo "❌ CONNECTION FAILED: " . $mysqli->connect_error . "\n";
        exit(1);
    }
    
    echo "✅ Connected to database!\n\n";
    
    // List all tables
    echo "=== TABLES IN DATABASE ===\n";
    $result = $mysqli->query("SHOW TABLES");
    $tableCount = 0;
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
        $tableCount++;
    }
    echo "\nTotal tables: $tableCount\n\n";
    
    // Check users table
    echo "=== USERS TABLE ===\n";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Users count: " . $row['count'] . "\n\n";
        
        // Show first admin user
        $result = $mysqli->query("SELECT id, username, email, phone FROM users WHERE active = 1 LIMIT 1");
        if ($result && $result->num_rows > 0) {
            echo "Sample user:\n";
            $user = $result->fetch_assoc();
            print_r($user);
        }
    } else {
        echo "❌ Error querying users: " . $mysqli->error . "\n";
    }
    
    // Check settings
    echo "\n=== SETTINGS TABLE ===\n";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM settings");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Settings count: " . $row['count'] . "\n";
    } else {
        echo "❌ Error querying settings: " . $mysqli->error . "\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
