<?php
// Simple test file to check if PHP and environment variables are working
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Backend is running',
    'php_version' => phpversion(),
    'getenv' => [
        'DB_HOST' => getenv('DB_HOST') ?: 'NOT SET',
        'DB_USER' => getenv('DB_USER') ?: 'NOT SET',
        'DB_NAME' => getenv('DB_NAME') ?: 'NOT SET',
        'DB_PORT' => getenv('DB_PORT') ?: 'NOT SET',
        'CI_ENVIRONMENT' => getenv('CI_ENVIRONMENT') ?: 'NOT SET',
    ],
    '$_ENV' => [
        'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT SET',
        'DB_USER' => $_ENV['DB_USER'] ?? 'NOT SET',
        'DB_NAME' => $_ENV['DB_NAME'] ?? 'NOT SET',
        'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT SET',
        'CI_ENVIRONMENT' => $_ENV['CI_ENVIRONMENT'] ?? 'NOT SET',
    ],
    '$_SERVER' => [
        'DB_HOST' => $_SERVER['DB_HOST'] ?? 'NOT SET',
        'DB_USER' => $_SERVER['DB_USER'] ?? 'NOT SET',
        'DB_NAME' => $_SERVER['DB_NAME'] ?? 'NOT SET',
        'DB_PORT' => $_SERVER['DB_PORT'] ?? 'NOT SET',
        'CI_ENVIRONMENT' => $_SERVER['CI_ENVIRONMENT'] ?? 'NOT SET',
    ],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
