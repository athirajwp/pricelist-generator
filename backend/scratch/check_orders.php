<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

$ordersWithEmail = Order::whereNotNull('email')->where('email', '!=', '')->get();
echo "Found " . count($ordersWithEmail) . " orders with email:\n";
foreach ($ordersWithEmail as $o) {
    echo "- Order ID={$o->id}, Number={$o->order_number}, Email={$o->email}\n";
}
