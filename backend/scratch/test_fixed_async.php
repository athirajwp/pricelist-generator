<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$orderId = 25;

$toolsPhp = base_path('.tools/php/php.exe');
$phpExecutable = file_exists($toolsPhp) ? $toolsPhp : (PHP_BINARY ?: 'php');
$artisan = base_path('artisan');

if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
    $phpExecutable = str_replace('/', '\\', $phpExecutable);
    $artisan = str_replace('/', '\\', $artisan);
    $cmd = sprintf('cmd /c start /B "" "%s" "%s" order:send-emails %d', $phpExecutable, $artisan, (int)$orderId);
    echo "Executing Windows command:\n$cmd\n";
    pclose(popen($cmd, "r"));
} else {
    $cmd = sprintf('"%s" "%s" order:send-emails %d > /dev/null 2>&1 &', $phpExecutable, $artisan, (int)$orderId);
    exec($cmd);
}

echo "Done trigger!\n";
