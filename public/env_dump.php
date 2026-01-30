<?php
// Dump ALL environment variables to see what Railway is actually passing
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

echo "=== ALL ENVIRONMENT VARIABLES ===\n\n";

// Get all environment variables
$env = getenv();

if (empty($env)) {
    echo "❌ getenv() returned EMPTY!\n\n";
    echo "Trying \$_ENV:\n";
    print_r($_ENV);
    echo "\n\nTrying \$_SERVER:\n";
    print_r($_SERVER);
} else {
    echo "Environment variables from getenv():\n";
    ksort($env);
    foreach ($env as $key => $value) {
        // Hide sensitive values
        if (stripos($key, 'PASSWORD') !== false || stripos($key, 'KEY') !== false || stripos($key, 'SECRET') !== false) {
            $value = substr($value, 0, 3) . '***';
        }
        echo "$key = $value\n";
    }
}

echo "\n\n=== Looking for Database Variables ===\n";
$db_vars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_PORT', 'DATABASE_URL', 'MYSQL_HOST', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_DATABASE', 'MYSQL_PORT'];

foreach ($db_vars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        if (stripos($var, 'PASSWORD') !== false) {
            $value = substr($value, 0, 3) . '***';
        }
        echo "✓ $var = $value\n";
    } else {
        echo "❌ $var = NOT SET\n";
    }
}
