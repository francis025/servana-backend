<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing get_settings function...\n\n";

// Manually test the get_settings function
$mysqli = new mysqli('tramway.proxy.rlwy.net', 'root', 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ', 'railway', 48486);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$result = $mysqli->query("SELECT * FROM settings WHERE variable = 'general_settings'");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✓ general_settings found\n";
    echo "Value length: " . strlen($row['value']) . " bytes\n\n";
    
    $settings = json_decode($row['value'], true);
    if ($settings === null) {
        echo "✗ JSON decode failed! Error: " . json_last_error_msg() . "\n";
        echo "Raw value (first 500 chars):\n" . substr($row['value'], 0, 500) . "\n";
    } else {
        echo "✓ JSON decoded successfully\n";
        echo "Keys: " . implode(', ', array_keys($settings)) . "\n\n";
        
        if (isset($settings['company_title'])) {
            echo "company_title type: " . gettype($settings['company_title']) . "\n";
            if (is_array($settings['company_title'])) {
                echo "company_title languages: " . implode(', ', array_keys($settings['company_title'])) . "\n";
            } else {
                echo "company_title value: " . $settings['company_title'] . "\n";
            }
        } else {
            echo "✗ company_title not found in settings!\n";
        }
    }
} else {
    echo "✗ general_settings NOT found\n";
}

$mysqli->close();
?>
