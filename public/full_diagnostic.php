<?php
// Bypass CodeIgniter and test everything directly
header('Content-Type: text/plain');

echo "=== FULL DIAGNOSTIC ===\n\n";

// 1. Database connection
$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$password = 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
$database = 'railway';
$port = 48486;

$mysqli = new mysqli($host, $user, $password, $database, $port);
if ($mysqli->connect_error) {
    die("❌ DB Connection failed: " . $mysqli->connect_error);
}
echo "✅ Database connected\n\n";

// 2. Check critical tables
$tables = ['users', 'settings', 'groups', 'users_groups'];
foreach ($tables as $table) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetch_assoc();
    echo "$table: {$row['count']} rows\n";
}

echo "\n=== Settings Details ===\n";
$result = $mysqli->query("SELECT variable, LEFT(value, 50) as value FROM settings LIMIT 20");
while ($row = $result->fetch_assoc()) {
    echo "{$row['variable']}: {$row['value']}\n";
}

echo "\n=== Admin User ===\n";
$result = $mysqli->query("SELECT u.id, u.username, u.email, u.phone, u.active, g.name as group_name 
                          FROM users u 
                          LEFT JOIN users_groups ug ON u.id = ug.user_id 
                          LEFT JOIN groups g ON ug.group_id = g.id 
                          WHERE u.id = 1");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    print_r($user);
} else {
    echo "❌ No admin user found!\n";
}

echo "\n=== Testing CodeIgniter Bootstrap ===\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader works\n";
    
    // Try to load CodeIgniter
    define('FCPATH', __DIR__ . '/');
    require_once __DIR__ . '/../app/Config/Paths.php';
    echo "✅ Paths loaded\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$mysqli->close();
