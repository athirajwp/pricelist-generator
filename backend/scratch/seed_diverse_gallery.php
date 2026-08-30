<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

$diverseImages = [
    'uploads/products/1780636675_6a225c032940b.jpg',
    'uploads/products/1780637194_6a225e0adefb1.jpg',
    'uploads/products/1780637300_6a225e747975f.webp',
    'uploads/products/1780637389_6a225ecd33aba.jpg',
    'uploads/products/1780637886_6a2260be25ca8.jpg',
    'uploads/products/1780637940_6a2260f4d759a.jpg',
    'uploads/products/1780638050_6a2261624b3ef.webp',
    'uploads/products/1780638168_6a2261d871309.jpg',
    'uploads/products/1780638239_6a22621f4434c.jpg',
    'uploads/products/1780638387_6a2262b3f1552.jpg',
    'images/about_showcase.png',
    'uploads/products/1780636675_6a225c032940b.jpg', // wrap around
];

foreach (Company::all() as $company) {
    echo "=====================================\n";
    echo "Seeding gallery for Company: {$company->name} ({$company->code})\n";
    
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $company->code));
    
    // Switch connection
    config(["database.connections.pgsql.database" => $tenantDb]);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
    
    try {
        // We will seed up to 12 images
        for ($i = 1; $i <= 12; $i++) {
            $path = $diverseImages[$i - 1];
            Setting::set("gallery_image_{$i}", $path, 'text');
            echo "  gallery_image_{$i} set to: {$path}\n";
        }
        
        // Ensure slots 13-18 are initialized/empty so they can be customized by the admin
        for ($i = 13; $i <= 18; $i++) {
            if (!Setting::where('key', "gallery_image_{$i}")->exists()) {
                Setting::set("gallery_image_{$i}", '', 'text');
                echo "  gallery_image_{$i} initialized to empty.\n";
            }
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
echo "Seeding completed successfully!\n";
