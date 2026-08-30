<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

$marqueeHTML = <<<'HTML'
<span class="text-gold-200"><i class="fa-solid fa-bullhorn text-gold-300 mr-2"></i><strong>Fresh and Warm Bakes Everyday</strong></span>
<span><i class="fa-solid fa-circle-exclamation text-gold-300 mr-2"></i>Minimum Order Value for Sivakasi Delivery is <strong>₹1000</strong></span>
<span><i class="fa-solid fa-fire text-gold-300 mr-2"></i>Celebrate Diwali / Festivals with Flat <strong>60% Discount</strong>!</span>
<span><i class="fa-solid fa-truck-fast text-gold-300 mr-2"></i>Express Lorry Transport Delivery Across Kerala, Karnataka, Tamilnadu, Andhra & Telangana!</span>
<span><i class="fa-solid fa-phone text-gold-300 mr-2"></i>For Enquiries, Contact Support: <strong>8682942042</strong></span>
<span><i class="fa-solid fa-shield-halved text-gold-300 mr-2"></i>100% Quality & Safe Sivakasi Manufactured Crackers</span>
HTML;

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Updating marquee alert setting for Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        Setting::set("marquee_html", $marqueeHTML, 'textarea');
        echo "  marquee_html seeded successfully.\n";
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
echo "Marquee alerts seeding completed successfully!\n";
