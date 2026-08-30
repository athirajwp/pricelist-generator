<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$databases = DB::select("SHOW DATABASES");
echo "Databases found on MySQL server:\n";
foreach ($databases as $db) {
    $dbName = reset($db);
    if (str_contains($dbName, 'cracker')) {
        echo "- " . $dbName . "\n";
    }
}
