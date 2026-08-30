<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CheckoutController;
use App\Models\Order;

$order = Order::orderBy('id', 'desc')->first();
echo "Testing dispatchOrderEmailsAsync for order ID {$order->id}...\n";

CheckoutController::dispatchOrderEmailsAsync($order->id);
echo "Dispatched!\n";
