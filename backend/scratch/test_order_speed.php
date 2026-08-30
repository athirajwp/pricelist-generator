<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CheckoutController;
use Illuminate\Http\Request;

$start = microtime(true);

$request = Request::create('/api/checkout', 'POST', [
    'name' => 'Speed Test Customer',
    'phone' => '9876543210',
    'whatsapp' => '9876543210',
    'email' => 'speedtest@example.com',
    'address' => '123 Speed Street',
    'city' => 'Sivakasi',
    'state' => 'Tamil Nadu',
    'pincode' => '626123',
    'items' => [
        ['id' => 1, 'qty' => 70]
    ]
]);

$controller = new CheckoutController();
$response = $controller->store($request);

$end = microtime(true);
$duration = round(($end - $start) * 1000, 2);

echo "Order Placement Duration: {$duration} ms\n";
echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Content: " . $response->getContent() . "\n";
