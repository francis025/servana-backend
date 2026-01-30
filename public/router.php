<?php
/**
 * Router script for PHP built-in server
 * This handles URL rewriting that .htaccess would normally do
 */

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the file exists and is not index.php, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // Serve the file as-is
}

// Otherwise, route everything through index.php
$_SERVER['PATH_INFO'] = $uri;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
