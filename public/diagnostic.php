<?php
header('Content-Type: text/plain');

echo "=== Railway PHP Diagnostic ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Environment: " . getenv('CI_ENVIRONMENT') . "\n\n";

echo "=== Loaded Extensions ===\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- $ext\n";
}

echo "\n=== GD Extension ===\n";
if (extension_loaded('gd')) {
    echo "✓ GD is loaded\n";
    $gd_info = gd_info();
    foreach ($gd_info as $key => $value) {
        echo "$key: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "\n";
    }
} else {
    echo "✗ GD is NOT loaded\n";
}

echo "\n=== Database Connection ===\n";
try {
    $host = getenv('DB_HOST') ?: 'tramway.proxy.rlwy.net';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
    $db = getenv('DB_NAME') ?: 'railway';
    $port = getenv('DB_PORT') ?: 48486;
    
    echo "Host: $host:$port\n";
    echo "Database: $db\n";
    
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli->connect_error) {
        echo "✗ Connection failed: " . $mysqli->connect_error . "\n";
    } else {
        echo "✓ Connected successfully\n";
        
        // Check general_settings
        $result = $mysqli->query("SELECT COUNT(*) as count FROM settings WHERE variable = 'general_settings'");
        $row = $result->fetch_assoc();
        echo "general_settings count: " . $row['count'] . "\n";
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Environment Variables ===\n";
$env_vars = ['CI_ENVIRONMENT', 'DB_HOST', 'DB_USER', 'DB_NAME', 'DB_PORT', 'app.baseURL'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value) {
        if (strpos($var, 'PASSWORD') !== false) {
            echo "$var: ***hidden***\n";
        } else {
            echo "$var: $value\n";
        }
    } else {
        echo "$var: (not set)\n";
    }
}

echo "\n=== File Permissions ===\n";
$writable_dir = __DIR__ . '/../writable';
echo "Writable directory: $writable_dir\n";
echo "Exists: " . (file_exists($writable_dir) ? 'Yes' : 'No') . "\n";
echo "Writable: " . (is_writable($writable_dir) ? 'Yes' : 'No') . "\n";

echo "\n=== Done ===\n";
?>
