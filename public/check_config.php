<?php
// Check if Database.php was modified by the build script
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

$configFile = __DIR__ . '/../app/Config/Database.php';

if (file_exists($configFile)) {
    echo "=== Database.php Contents ===\n\n";
    
    $content = file_get_contents($configFile);
    
    // Extract the relevant lines
    preg_match("/'hostname' => '([^']+)'/", $content, $hostname);
    preg_match("/'username' => '([^']+)'/", $content, $username);
    preg_match("/'password' => '([^']+)'/", $content, $password);
    preg_match("/'database' => '([^']+)'/", $content, $database);
    preg_match("/'port'\s+=> (\d+)/", $content, $port);
    
    echo "Hostname: " . ($hostname[1] ?? 'NOT FOUND') . "\n";
    echo "Username: " . ($username[1] ?? 'NOT FOUND') . "\n";
    echo "Password: " . (isset($password[1]) ? substr($password[1], 0, 3) . '***' : 'NOT FOUND') . "\n";
    echo "Database: " . ($database[1] ?? 'NOT FOUND') . "\n";
    echo "Port: " . ($port[1] ?? 'NOT FOUND') . "\n";
    
    echo "\n=== Build Script Status ===\n";
    if (($hostname[1] ?? '') === 'localhost') {
        echo "❌ FAILED: Database.php was NOT modified by build script!\n";
        echo "   Hostname is still 'localhost'\n";
    } else {
        echo "✓ SUCCESS: Database.php was modified!\n";
    }
    
    echo "\n=== Environment Variables (for debugging) ===\n";
    echo "DB_HOST env: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    echo "DB_USER env: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
    echo "DB_NAME env: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
    
} else {
    echo "ERROR: Database.php not found at: $configFile\n";
}
