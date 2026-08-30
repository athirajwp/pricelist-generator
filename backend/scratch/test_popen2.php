<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$toolsPhp = str_replace('/', '\\', base_path('.tools/php/php.exe'));
$phpExecutable = file_exists($toolsPhp) ? $toolsPhp : (PHP_BINARY ? str_replace('/', '\\', PHP_BINARY) : 'php');
$artisan = str_replace('/', '\\', base_path('artisan'));
$orderId = 26;

$log = str_replace('/', '\\', base_path('storage/logs/cmd_test2.log'));

// Test 1: Using WScript.Shell / proc_open / popen
$cmd = sprintf('cmd /c start /B "" "%s" "%s" order:send-emails %d > "%s" 2>&1', $phpExecutable, $artisan, (int)$orderId, $log);
echo "Executing CMD:\n$cmd\n";

pclose(popen($cmd, "r"));
sleep(5);

if (file_exists($log)) {
    echo "Log output:\n" . file_get_contents($log) . "\n";
} else {
    echo "Log file was NOT created!\n";
}
