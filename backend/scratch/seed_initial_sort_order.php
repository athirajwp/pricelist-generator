<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Initializing product sort_orders for Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        $products = Product::all();
        foreach ($products as $p) {
            $p->update(['sort_order' => $p->id]);
        }
        echo "  Product sort_orders initialized successfully.\n";
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
echo "Initialization completed successfully!\n";
