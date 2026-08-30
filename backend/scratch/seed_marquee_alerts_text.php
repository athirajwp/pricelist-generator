<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

$alerts = [
    1 => 'Fresh and Warm Bakes Everyday',
    2 => 'Minimum Order Value for Sivakasi Delivery is <strong>₹1000</strong>',
    3 => 'Celebrate Diwali / Festivals with Flat <strong>60% Discount</strong>!',
    4 => 'Express Lorry Transport Delivery Across Kerala, Karnataka, Tamilnadu, Andhra & Telangana!',
    5 => 'For Enquiries, Contact Support: <strong>8682942042</strong>',
    6 => '100% Quality & Safe Sivakasi Manufactured Crackers',
];

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Seeding text-based marquee alerts for Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        // Remove old marquee_html if it exists to keep database clean
        Setting::where('key', 'marquee_html')->delete();
        
        foreach ($alerts as $num => $text) {
            Setting::set("marquee_alert_{$num}", $text, 'text');
            echo "  marquee_alert_{$num} seeded successfully.\n";
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
echo "Seeding completed successfully!\n";
