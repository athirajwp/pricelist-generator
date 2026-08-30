<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

// Switch connection database to crackers2_crackersdemo
$defaultConn = config('database.default');
config(["database.connections.{$defaultConn}.database" => 'crackers2_crackersdemo']);
DB::purge($defaultConn);
DB::reconnect($defaultConn);

Setting::set('slider_image_2', 'img/slider img/21281.jpg', 'text');
echo "Restored slider_image_2 value to database.\n";
