<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Global Configuration
 */

if (defined('CURTISS_CONFIG_LOADED')) {
    return;
}
define('CURTISS_CONFIG_LOADED', true);

// Timezone & Locale
date_default_timezone_set('Asia/Colombo');

// Error Handling: display on local dev, log without breaking UI on live server
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

if ($isLocal) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Session Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// App Constants
if (!defined('APP_NAME'))        define('APP_NAME', 'Curtiss Admin');
if (!defined('APP_COMPANY'))     define('APP_COMPANY', 'Curtiss');
if (!defined('APP_VERSION'))     define('APP_VERSION', '1.0.0');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', 'LKR ');

// Base URL calculation
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = rtrim($protocol . $host . $scriptDir, '/');
    define('BASE_URL', $baseUrl);
}

// Database Configuration
if (!defined('DB_HOST'))    define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT'))    define('DB_PORT', '3306');
if (!defined('DB_NAME'))    define('DB_NAME', 'trycurtiss_admin');
if (!defined('DB_USER'))    define('DB_USER', 'trycurtiss_admin');
if (!defined('DB_PASS'))    define('DB_PASS', 'Susara@200611003614');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
