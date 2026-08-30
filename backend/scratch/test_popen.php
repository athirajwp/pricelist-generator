<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$toolsPhp = base_path('.tools/php/php.exe');
$phpExecutable = file_exists($toolsPhp) ? $toolsPhp : (PHP_BINARY ?: 'php');
$artisan = base_path('artisan');
$orderId = 26;

$cmd = sprintf('start /B "" "%s" "%s" order:send-emails %d > NUL 2>&1', $phpExecutable, $artisan, (int)$orderId);
echo "Executing CMD:\n$cmd\n";

$log = base_path('storage/logs/cmd_test.log');
$cmdWithLog = sprintf('start /B "" "%s" "%s" order:send-emails %d > "%s" 2>&1', $phpExecutable, $artisan, (int)$orderId, $log);
echo "Executing CMD with Log:\n$cmdWithLog\n";

pclose(popen($cmdWithLog, "r"));
sleep(2);

if (file_exists($log)) {
    echo "Log output:\n" . file_get_contents($log) . "\n";
} else {
    echo "Log file was NOT created!\n";
}
