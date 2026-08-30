<?php

use Illuminate\Support\Facades\Artisan;
use App\Models\Order;
use App\Models\Setting;
use App\Mail\AdminInvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('order:send-email {orderId} {--tenant-db=}', function ($orderId) {
    if ($tenantDb = $this->option('tenant-db')) {
        $defaultConn = config('database.default');
        config(["database.connections.{$defaultConn}.database" => $tenantDb]);
        \Illuminate\Support\Facades\DB::purge($defaultConn);
        \Illuminate\Support\Facades\DB::reconnect($defaultConn);
    }

    $order = Order::find($orderId);
    if (!$order) {
        $this->error("Order not found!");
        return;
    }

    try {
        Log::info("Starting background email dispatch for order {$order->order_number} (ID: {$orderId})");
        $adminEmail   = Setting::get('store_email', config('mail.from.address'));
        $customerEmail = $order->email;

        $order->load('items');

        // Send admin notification (invoice view)
        if (!empty($adminEmail)) {
            Mail::to($adminEmail)->send(new AdminInvoiceMail($order));
            $this->info("Admin email sent to {$adminEmail}");
        }

        // Send customer confirmation (friendly view) — only if customer provided their email
        if (!empty($customerEmail)) {
            Mail::to($customerEmail)->send(new \App\Mail\CustomerOrderMail($order));
            $this->info("Customer email sent to {$customerEmail}");
        }

        $this->info("Email dispatch complete for order {$order->order_number}");
        Log::info("Background email dispatch completed successfully for order {$order->order_number}");
    } catch (\Exception $e) {
        Log::error('Failed to send order email notification in background: ' . $e->getMessage());
        $this->error('Failed: ' . $e->getMessage());
    }
})->purpose('Send order invoice email to admin and customer in the background');

Artisan::command('serve:all', function () {
    $companiesCount = \App\Models\Company::count();
    $this->info("Starting local development servers for {$companiesCount} companies...");

    for ($i = 0; $i < $companiesCount; $i++) {
        $port = 7001 + $i;
        $this->line("Starting server on port {$port}...");
        
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            pclose(popen("start \"Artisan Serve Port {$port}\" cmd /c \"artisan.bat serve --port={$port}\"", "r"));
        } else {
            exec("php artisan serve --port={$port} > /dev/null 2>&1 &");
        }
    }

    $this->info("All servers launched successfully!");
})->purpose('Start local development servers on sequential ports for all registered companies');

