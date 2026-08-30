<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminInvoiceMail;
use App\Mail\CustomerOrderMail;

$order = Order::with('items')->orderBy('id', 'desc')->first();
$adminEmail = Setting::get('store_email', config('mail.from.address'));

echo "Sending AdminInvoiceMail for Order #{$order->order_number} to {$adminEmail}...\n";
$start = microtime(true);

try {
    Mail::to($adminEmail)->send(new AdminInvoiceMail($order));
    $end = microtime(true);
    echo "SUCCESS: Admin email sent in " . round($end - $start, 2) . "s!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

if ($order->email) {
    echo "Sending CustomerOrderMail for Order #{$order->order_number} to {$order->email}...\n";
    $start = microtime(true);
    try {
        Mail::to($order->email)->send(new CustomerOrderMail($order));
        $end = microtime(true);
        echo "SUCCESS: Customer email sent in " . round($end - $start, 2) . "s!\n";
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
