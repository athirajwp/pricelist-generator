<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$orderId = 25;

$toolsPhp = base_path('.tools/php/php.exe');
$phpExecutable = file_exists($toolsPhp) ? $toolsPhp : (PHP_BINARY ?: 'php');
$artisan = base_path('artisan');
$logFile = storage_path('logs/email_background.log');

$phpExecutable = str_replace('/', '\\', $phpExecutable);
$artisan = str_replace('/', '\\', $artisan);
$logFile = str_replace('/', '\\', $logFile);

echo "Testing proc_open detached...\n";

if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
    $cmd = sprintf('"%s" "%s" order:send-emails %d', $phpExecutable, $artisan, (int)$orderId);
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["file", $logFile, "a"],
        2 => ["file", $logFile, "a"]
    ];
    $process = proc_open($cmd, $descriptorspec, $pipes, base_path(), null, ['bypass_shell' => true]);
    if (is_resource($process)) {
        fclose($pipes[0]);
        // Do NOT proc_close($process), let it run detached!
        echo "proc_open process started asynchronously!\n";
    } else {
        echo "proc_open failed!\n";
    }
} else {
    $cmd = sprintf('"%s" "%s" order:send-emails %d >> "%s" 2>&1 &', $phpExecutable, $artisan, (int)$orderId, $logFile);
    exec($cmd);
}

echo "Checking log file after 15 seconds...\n";
sleep(15);
if (file_exists($logFile)) {
    echo "Log output:\n" . file_get_contents($logFile) . "\n";
}
