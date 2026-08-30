<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$orderId = 25;

$toolsPhp = base_path('.tools/php/php.exe');
$phpExecutable = file_exists($toolsPhp) ? $toolsPhp : (PHP_BINARY ?: 'php');
$artisan = base_path('artisan');
$logFile = storage_path('logs/email_background.log');

if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
    $phpExecutable = str_replace('/', '\\', $phpExecutable);
    $artisan = str_replace('/', '\\', $artisan);
    $logFile = str_replace('/', '\\', $logFile);
    $cmd = sprintf('cmd /c start /B "" "%s" "%s" order:send-emails %d >> "%s" 2>&1', $phpExecutable, $artisan, (int)$orderId, $logFile);
    echo "Executing Windows command:\n$cmd\n";
    pclose(popen($cmd, "r"));
} else {
    $cmd = sprintf('"%s" "%s" order:send-emails %d >> "%s" 2>&1 &', $phpExecutable, $artisan, (int)$orderId, $logFile);
    exec($cmd);
}

echo "Triggered! Checking log after 10s...\n";
sleep(10);
if (file_exists($logFile)) {
    echo "Log contents:\n" . file_get_contents($logFile) . "\n";
} else {
    echo "Log file not found.\n";
}
