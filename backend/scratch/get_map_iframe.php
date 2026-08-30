<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Switch connection to crackers2_crackersdemo
$defaultConn = config('database.default');
config(["database.connections.{$defaultConn}.database" => 'crackers2_crackersdemo']);
\Illuminate\Support\Facades\DB::purge($defaultConn);
\Illuminate\Support\Facades\DB::reconnect($defaultConn);

echo "Iframe: " . \App\Models\Setting::get('store_map_iframe') . "\n";
