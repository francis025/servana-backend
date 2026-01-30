<?php
/**
 * DIRECT DATABASE CONNECTION TEST
 * Bypasses all CodeIgniter files and connects directly using env vars
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

echo "=== DIRECT DATABASE CONNECTION TEST ===\n\n";

// Get credentials from environment
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'railway';
$port = getenv('DB_PORT') ?: 3306;

echo "Attempting connection with:\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Database: $database\n";
echo "Port: $port\n";
echo "Password: " . (empty($password) ? 'EMPTY' : substr($password, 0, 3) . '***') . "\n\n";

// Try to connect
try {
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo "❌ CONNECTION FAILED!\n";
        echo "Error: " . $mysqli->connect_error . "\n";
        echo "Error Code: " . $mysqli->connect_errno . "\n";
        exit(1);
    }
    
    echo "✅ CONNECTION SUCCESSFUL!\n\n";
    
    // Test query
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Query test passed!\n";
        echo "Users table has {$row['count']} records\n\n";
    } else {
        echo "⚠️  Connection works but query failed: " . $mysqli->error . "\n";
        echo "This might mean the database is empty or tables don't exist yet.\n\n";
    }
    
    // Show database info
    echo "=== Database Info ===\n";
    echo "Server version: " . $mysqli->server_info . "\n";
    echo "Host info: " . $mysqli->host_info . "\n";
    echo "Protocol version: " . $mysqli->protocol_version . "\n";
    echo "Character set: " . $mysqli->character_set_name() . "\n";
    
    $mysqli->close();
    
    echo "\n✅ DATABASE IS WORKING!\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    exit(1);
}
