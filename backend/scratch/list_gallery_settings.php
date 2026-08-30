<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        $settings = Setting::where('key', 'like', 'gallery_%')->orderBy('key')->get();
        if ($settings->isEmpty()) {
            echo "No gallery settings found.\n";
        } else {
            foreach ($settings as $s) {
                echo "  {$s->key}: {$s->value}\n";
            }
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
