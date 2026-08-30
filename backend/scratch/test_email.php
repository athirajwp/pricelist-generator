<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminInvoiceMail;
use App\Mail\CustomerOrderMail;

try {
// Using default database connection from .env / config
    $order = Order::orderBy('id', 'desc')->first();
    if (!$order) {
        echo "No orders found in crackers2_crackersdemo database.\n";
        exit;
    }
    
    echo "Found order: ID={$order->id}, Number={$order->order_number}, Customer Email={$order->email}\n";
    
    $adminEmail = Setting::get('store_email', config('mail.from.address'));
    echo "Sending Admin Invoice Mail to: {$adminEmail}...\n";
    
    Mail::to($adminEmail)->send(new AdminInvoiceMail($order));
    echo "SUCCESS: Admin email sent!\n";
    
    if ($order->email) {
        echo "Sending Customer Order Mail to: {$order->email}...\n";
        Mail::to($order->email)->send(new CustomerOrderMail($order));
        echo "SUCCESS: Customer email sent!\n";
    } else {
        echo "Skipping customer mail (no email provided for order).\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: Failed to send email!\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
