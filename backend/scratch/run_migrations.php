<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Running migrations for Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        echo "  Migrations completed successfully.\n";
        echo Artisan::output();
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
echo "Migrations run across all tenants completed successfully!\n";
