<?php
header('Content-Type: text/plain');

echo "=== FIXING DUPLICATE SETTINGS ===\n\n";

$host = 'tramway.proxy.rlwy.net';
$user = 'root';
$password = 'eLdPrydgjzlFIQaElBClUgcBgvSmGkjZ';
$database = 'railway';
$port = 48486;

$mysqli = new mysqli($host, $user, $password, $database, $port);
if ($mysqli->connect_error) {
    die("❌ Connection failed\n");
}

echo "✅ Connected\n\n";

// Delete all duplicates, keeping only the one with the lowest ID
$sql = "DELETE s1 FROM settings s1
        INNER JOIN settings s2 
        WHERE s1.id > s2.id 
        AND s1.variable = s2.variable";

if ($mysqli->query($sql)) {
    echo "✅ Removed duplicates\n";
    echo "Affected rows: " . $mysqli->affected_rows . "\n\n";
} else {
    echo "❌ Error: " . $mysqli->error . "\n";
}

// Show remaining settings
$result = $mysqli->query("SELECT COUNT(*) as count FROM settings");
$row = $result->fetch_assoc();
echo "Settings remaining: " . $row['count'] . "\n\n";

echo "Current settings:\n";
$result = $mysqli->query("SELECT variable FROM settings ORDER BY variable");
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['variable'] . "\n";
}

$mysqli->close();

echo "\n✅ DONE! Try /admin/login now!\n";
