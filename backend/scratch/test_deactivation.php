<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;

$company = Company::find(1);
$originalStatus = $company->status;

echo "Original status: {$originalStatus}\n";

// 1. Set to inactive
$company->status = 'inactive';
$company->save();
echo "Set status to inactive. Querying localhost:8001...\n";

// Run curl check
$ch = curl_init('http://localhost:8001/?company=crackersdemo1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: {$httpCode}\n";
if (str_contains($response, 'Website Inactive')) {
    echo "SUCCESS: Found 'Website Inactive' text in response!\n";
} else {
    echo "FAILED: Could not find 'Website Inactive' in response.\n";
}

// 2. Restore status to active
$company->status = 'active';
$company->save();
echo "Restored status to active. Querying localhost:8001...\n";

$ch = curl_init('http://localhost:8001/?company=crackersdemo1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: {$httpCode}\n";
if ($httpCode === 200) {
    echo "SUCCESS: Storefront loaded successfully (200 OK)!\n";
} else {
    echo "FAILED: Storefront returned HTTP {$httpCode}\n";
}
