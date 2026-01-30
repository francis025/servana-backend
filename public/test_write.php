<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

$configFile = __DIR__ . '/../app/Config/Database.php';

echo "=== File Write Test ===\n\n";
echo "Config file: $configFile\n";
echo "File exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "\n";
echo "File readable: " . (is_readable($configFile) ? 'YES' : 'NO') . "\n";
echo "File writable: " . (is_writable($configFile) ? 'YES' : 'NO') . "\n";
echo "File permissions: " . substr(sprintf('%o', fileperms($configFile)), -4) . "\n";

$dir = dirname($configFile);
echo "\nDirectory: $dir\n";
echo "Directory writable: " . (is_writable($dir) ? 'YES' : 'NO') . "\n";
echo "Directory permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";

echo "\n=== Current File Contents (first 500 chars) ===\n";
echo substr(file_get_contents($configFile), 0, 500);
