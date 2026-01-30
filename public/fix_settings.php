<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

// Direct database connection
$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$password = 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
$database = 'railway';
$port = 48486;

echo "=== FIXING SETTINGS TABLE ===\n\n";

try {
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        die("❌ CONNECTION FAILED: " . $mysqli->connect_error . "\n");
    }
    
    echo "✅ Connected to database!\n\n";
    
    // Insert minimal required settings
    $settings = [
        ['app_name', 'Servana'],
        ['support_email', 'support@servana.com'],
        ['support_number', '+1234567890'],
        ['currency', 'USD'],
        ['currency_code', '$'],
        ['time_zone', 'Asia/Kolkata'],
        ['max_serviceable_distance', '50'],
        ['country_code', '+1'],
        ['range_units', 'kilometers'],
    ];
    
    $inserted = 0;
    foreach ($settings as $setting) {
        $variable = $mysqli->real_escape_string($setting[0]);
        $value = $mysqli->real_escape_string($setting[1]);
        
        $sql = "INSERT INTO settings (variable, value, created_at) 
                VALUES ('$variable', '$value', NOW())
                ON DUPLICATE KEY UPDATE value = '$value'";
        
        if ($mysqli->query($sql)) {
            echo "✅ Inserted/Updated: $variable\n";
            $inserted++;
        } else {
            echo "❌ Failed: $variable - " . $mysqli->error . "\n";
        }
    }
    
    echo "\n✅ Inserted/Updated $inserted settings!\n\n";
    
    // Verify
    $result = $mysqli->query("SELECT COUNT(*) as count FROM settings");
    $row = $result->fetch_assoc();
    echo "Total settings in database: " . $row['count'] . "\n";
    
    $mysqli->close();
    
    echo "\n✅ DONE! Try accessing /admin/login now!\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
