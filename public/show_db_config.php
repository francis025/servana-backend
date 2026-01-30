<?php
/**
 * Show the ACTUAL Database.php file contents
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

// Clear all caches
clearstatcache(true);
if (function_exists('opcache_reset')) {
    opcache_reset();
}

$configFile = __DIR__ . '/../app/Config/Database.php';

echo "=== RAW Database.php File Contents ===\n\n";
echo "File: $configFile\n";
echo "Exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($configFile)) . "\n\n";

echo "=== FILE CONTENTS ===\n";
echo file_get_contents($configFile);
