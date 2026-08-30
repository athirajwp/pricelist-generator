<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;

try {
    $companies = DB::connection('central')->table('companies')->get();
    echo "Found " . count($companies) . " companies in central database:\n";
    foreach ($companies as $comp) {
        $tenantDb = 'crackers2_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $comp->code));
        echo "- Company ID: {$comp->id} | Name: {$comp->name} | Code: {$comp->code} | Tenant DB: {$tenantDb}\n";
    }
} catch (\Exception $e) {
    echo "Error querying central companies: " . $e->getMessage() . "\n";
}
