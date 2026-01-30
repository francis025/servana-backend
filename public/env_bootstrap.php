<?php
/**
 * Environment Variable Bootstrap for Railway
 * This file loads environment variables from Railway into PHP's $_ENV superglobal
 * Must be included before any CodeIgniter files
 */

// List of environment variables we need
$required_vars = [
    'DB_HOST',
    'DB_USER',
    'DB_PASSWORD',
    'DB_NAME',
    'DB_PORT',
    'CI_ENVIRONMENT',
    'DECRYPTION_KEY',
    'DECRYPTION_IV',
    'PAYTM_ENCRYPTION_IV',
];

// Try to load from system environment
foreach ($required_vars as $var) {
    // Try getenv first
    $value = getenv($var);
    
    // If not found, try $_SERVER
    if ($value === false && isset($_SERVER[$var])) {
        $value = $_SERVER[$var];
    }
    
    // Set in $_ENV if we found it
    if ($value !== false) {
        $_ENV[$var] = $value;
        putenv("$var=$value");
    }
}

// Debug: Log what we found (remove in production)
if (getenv('CI_ENVIRONMENT') === 'development') {
    error_log('Environment variables loaded: ' . json_encode([
        'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT SET',
        'DB_USER' => $_ENV['DB_USER'] ?? 'NOT SET',
        'DB_NAME' => $_ENV['DB_NAME'] ?? 'NOT SET',
        'CI_ENVIRONMENT' => $_ENV['CI_ENVIRONMENT'] ?? 'NOT SET',
    ]));
}
