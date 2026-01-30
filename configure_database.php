<?php
/**
 * Configure Database.php with Railway environment variables
 * This runs at startup to write database credentials
 */

$configFile = __DIR__ . '/app/Config/Database.php';
$backupFile = $configFile . '.original';

echo "=== Configuring Database Credentials (PHP) ===\n";

// Get environment variables
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'servana';
$dbPort = getenv('DB_PORT') ?: '3306';

echo "DB_HOST: $dbHost\n";
echo "DB_USER: $dbUser\n";
echo "DB_NAME: $dbName\n";
echo "DB_PORT: $dbPort\n";

if (!file_exists($configFile)) {
    echo "❌ ERROR: Database.php not found at: $configFile\n";
    exit(1);
}

// Create backup if it doesn't exist
if (!file_exists($backupFile)) {
    copy($configFile, $backupFile);
    echo "✓ Created original backup\n";
}

// Read the file
$content = file_get_contents($backupFile);

// Replace values
$content = preg_replace(
    "/'hostname' => '[^']*'/",
    "'hostname' => '$dbHost'",
    $content
);

$content = preg_replace(
    "/'username' => '[^']*'/",
    "'username' => '$dbUser'",
    $content
);

$content = preg_replace(
    "/'password' => '[^']*'/",
    "'password' => '" . addslashes($dbPassword) . "'",
    $content
);

$content = preg_replace(
    "/'database' => '[^']*'/",
    "'database' => '$dbName'",
    $content
);

$content = preg_replace(
    "/'port'\s*=>\s*\d+/",
    "'port'     => $dbPort",
    $content
);

// Write the modified content
if (file_put_contents($configFile, $content)) {
    // Clear file stat cache to ensure changes are visible
    clearstatcache(true, $configFile);
    
    // Verify the write
    $verify = file_get_contents($configFile);
    if (strpos($verify, $dbHost) !== false) {
        echo "✓ Set DB_HOST to: $dbHost\n";
        echo "✓ Set DB_USER to: $dbUser\n";
        echo "✓ Set DB_PASSWORD (hidden)\n";
        echo "✓ Set DB_NAME to: $dbName\n";
        echo "✓ Set DB_PORT to: $dbPort\n";
        echo "✓ Verified changes are written\n";
        echo "=== Database Configuration Complete ===\n";
    } else {
        echo "⚠️  File written but verification failed!\n";
        echo "This might be a caching issue.\n";
    }
} else {
    echo "❌ ERROR: Failed to write to Database.php\n";
    exit(1);
}
