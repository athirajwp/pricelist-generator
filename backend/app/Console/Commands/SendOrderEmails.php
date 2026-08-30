<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Setting;
use App\Mail\AdminInvoiceMail;
use App\Mail\CustomerOrderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:send-emails {order_id} {--company=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send confirmation and invoice emails for an order in the background';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company');
        if ($companyId) {
            $company = \App\Models\Company::find($companyId);
            if ($company) {
                $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
                $defaultConn = config('database.default', 'mysql');
                config(["database.connections.{$defaultConn}.database" => $tenantDb]);
                \Illuminate\Support\Facades\DB::purge($defaultConn);
                \Illuminate\Support\Facades\DB::reconnect($defaultConn);
                view()->share('currentCompany', $company);

                if (!empty($company->smtp_host) && !empty($company->smtp_user) && !empty($company->smtp_pass)) {
                    $sslVal = strtolower((string)$company->smtp_ssl);
                    $encryption = ($sslVal === 'true' || $sslVal === 'ssl' || $company->smtp_port == 465) ? 'ssl' : 'tls';
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.transport' => 'smtp',
                        'mail.mailers.smtp.host' => trim($company->smtp_host),
                        'mail.mailers.smtp.port' => (int) ($company->smtp_port ?: 587),
                        'mail.mailers.smtp.encryption' => $encryption,
                        'mail.mailers.smtp.username' => trim($company->smtp_user),
                        'mail.mailers.smtp.password' => trim(str_replace(' ', '', $company->smtp_pass)),
                        'mail.from.address' => trim($company->smtp_user),
                        'mail.from.name' => $company->name ?: config('mail.from.name'),
                    ]);
                }
            }
        }

        $orderId = $this->argument('order_id');
        $order = Order::with('items')->find($orderId);

        if (!$order) {
            $this->error("Order #{$orderId} not found.");
            return 1;
        }

        try {
            $adminEmail = Setting::get('store_email', config('mail.from.address'));

            // Send Admin Invoice Notification Email
            if (!empty($adminEmail)) {
                try {
                    Mail::to($adminEmail)->send(new AdminInvoiceMail($order));
                    $this->info("Admin email sent to: {$adminEmail}");
                } catch (\Throwable $e) {
                    Log::error("Failed sending admin email for order #{$order->id}: " . $e->getMessage());
                }
            }

            // Send Customer Order Confirmation Email
            if (!empty($order->email)) {
                try {
                    Mail::to($order->email)->send(new CustomerOrderMail($order));
                    $this->info("Customer email sent to: {$order->email}");
                } catch (\Throwable $e) {
                    Log::error("Failed sending customer email for order #{$order->id}: " . $e->getMessage());
                }
            }

            return 0;
        } catch (\Throwable $e) {
            Log::error("Order email task failed for order #{$orderId}: " . $e->getMessage());
            return 1;
        }
    }
}
