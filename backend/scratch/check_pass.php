<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$company = \App\Models\Company::find(1); // Crackers Demo1
$tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
\Illuminate\Support\Facades\DB::disconnect();
config(['database.connections.pgsql.database' => $tenantDb]);
\Illuminate\Support\Facades\DB::reconnect();

echo "Admin password setting: " . \App\Models\Setting::get('admin_password', 'not set') . "\n";
