<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

$order = Order::with('items')->orderBy('id', 'desc')->first();
echo "Testing DomPDF rendering for order ID {$order->id}...\n";
$start = microtime(true);

try {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.invoice', [
        'order' => $order,
        'is_email_or_pdf' => true,
    ])->setPaper('a4', 'portrait')->setOptions([
        'isRemoteEnabled' => false,
        'isHtml5ParserEnabled' => true,
        'isFontSubsettingEnabled' => true,
    ]);

    $output = $pdf->output();
    $end = microtime(true);
    echo "SUCCESS: PDF generated in " . round($end - $start, 2) . "s! Size: " . strlen($output) . " bytes\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
