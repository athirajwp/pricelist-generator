<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

// Switch connection to crackersdemo1
config(["database.connections.pgsql.database" => "crackers_crackersdemo1"]);
DB::purge('pgsql');
DB::reconnect('pgsql');

$order = Order::first();
if ($order) {
    echo "Order ID: {$order->id} | Number: {$order->order_number}\n";
} else {
    echo "No orders found.\n";
}
