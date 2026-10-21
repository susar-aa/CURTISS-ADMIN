<?php
declare(strict_types=1);

/**
 * Curtiss Admin - Fallback Redirector
 * Catches any PWA shortcuts or requests directed to /public/index.php and forwards to root index.php
 */

$query = !empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '?page=dashboard';
header('Location: ../index.php' . $query, true, 301);
exit;
