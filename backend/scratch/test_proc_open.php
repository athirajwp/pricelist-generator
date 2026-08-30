<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$toolsPhp = str_replace('/', '\\', base_path('.tools/php/php.exe'));
$phpExecutable = file_exists($toolsPhp) ? $toolsPhp : (PHP_BINARY ? str_replace('/', '\\', PHP_BINARY) : 'php');
$artisan = str_replace('/', '\\', base_path('artisan'));
$orderId = 26;

$cmd = sprintf('"%s" "%s" order:send-emails %d 2>&1', $phpExecutable, $artisan, (int)$orderId);
echo "Executing:\n$cmd\n";

$descriptorspec = array(
   0 => array("pipe", "r"),
   1 => array("pipe", "w"),
   2 => array("pipe", "w")
);

$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    
    echo "STDOUT:\n$out\n";
    echo "STDERR:\n$err\n";
}
