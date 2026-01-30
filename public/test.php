<?php
// Simple test file to check if PHP and environment variables are working
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Backend is running',
    'php_version' => phpversion(),
    'environment' => [
        'DB_HOST' => getenv('DB_HOST') ?: 'NOT SET',
        'DB_USER' => getenv('DB_USER') ?: 'NOT SET',
        'DB_NAME' => getenv('DB_NAME') ?: 'NOT SET',
        'DB_PORT' => getenv('DB_PORT') ?: 'NOT SET',
        'CI_ENVIRONMENT' => getenv('CI_ENVIRONMENT') ?: 'NOT SET',
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
