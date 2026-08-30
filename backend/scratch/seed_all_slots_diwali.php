<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

$diwaliImagePath = 'images/gallery_diwali.jpg';

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Updating gallery for Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        // Update all 18 slots to the new Diwali image
        for ($i = 1; $i <= 18; $i++) {
            Setting::set("gallery_image_{$i}", $diwaliImagePath, 'text');
            echo "  gallery_image_{$i} set to: {$diwaliImagePath}\n";
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
echo "Gallery updating completed successfully!\n";
