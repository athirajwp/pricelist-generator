<?php

// Normalize SCRIPT_NAME on Hostinger/shared hosts when requests are rewritten into /backend/public/ or /public/
if (isset($_SERVER['SCRIPT_NAME']) && (str_starts_with($_SERVER['SCRIPT_NAME'], '/backend/public/') || str_starts_with($_SERVER['SCRIPT_NAME'], '/public/'))) {
    $_SERVER['SCRIPT_NAME'] = preg_replace('/^\/(backend\/public|public)/', '', $_SERVER['SCRIPT_NAME']);
}

if (strpos($_SERVER['REQUEST_URI'] ?? '', 'admin_sys/login') !== false) {
    $logData = sprintf(
        "[%s] %s %s\nCOOKIES: %s\nPOST: %s\n\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_SERVER['REQUEST_URI'] ?? '',
        json_encode($_COOKIE),
        json_encode($_POST)
    );
    file_put_contents(__DIR__ . '/../storage/logs/csrf_debug.log', $logData, FILE_APPEND);
}

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Auto-extract vendor.zip if vendor/autoload.php is missing (e.g. after Hostinger Git deployments)
if (!file_exists(__DIR__ . '/../vendor/autoload.php') && file_exists(__DIR__ . '/../vendor.zip')) {
    if (class_exists('ZipArchive')) {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $zip = new ZipArchive;
        if ($zip->open(__DIR__ . '/../vendor.zip') === TRUE) {
            $zip->extractTo(__DIR__ . '/../');
            $zip->close();
        }
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
