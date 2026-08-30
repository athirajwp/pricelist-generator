<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

$targetDatabases = ['crackers2', 'crackers2_crackers'];

foreach ($targetDatabases as $dbName) {
    try {
        echo "Seeding 200 products into database [$dbName]...\n";
        
        config(['database.connections.mysql.database' => $dbName]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        Product::query()->delete();
        Category::query()->delete();

        $categoriesData = [
            'Sparklers' => [
                '7cm Electric Sparklers', '7cm Green Sparklers', '7cm Red Sparklers', '10cm Electric Sparklers',
                '10cm Green Sparklers', '10cm Red Sparklers', '12cm Electric Sparklers', '12cm Green Sparklers',
                '12cm Red Sparklers', '15cm Multi-Color Sparklers', '30cm Electric Sparklers', '30cm Color Sparklers',
                '50cm Multi-Color Sparklers', '50cm Golden Sparklers', 'Mega Giant 100cm Sparklers'
            ],
            'Ground Chakkars' => [
                'Ground Chakkars Baby', 'Ground Chakkars Big', 'Ground Chakkars Special', 'Ground Chakkars Deluxe',
                'Disco Wheel (Spinning)', 'Whistling Chakkars', 'Tri-Color Spinning Wheel', 'Spinner King Deluxe',
                'Super Sonic Chakkar', 'Chakkar Special 5x'
            ],
            'Flower Pots' => [
                'Flower Pots Small', 'Flower Pots Big', 'Flower Pots Special', 'Flower Pots Deluxe',
                'Flower Pots Giant', 'Flower Pots Ashoora (Tri-color)', 'Super Color Koti', 'Mega Deluxe Flower Pot',
                'Color Changing Flower Pot', 'Golden Rain Flower Pot'
            ],
            'Fountains & Novelties' => [
                'Twinkling Stars', 'Pencil Small', 'Pencil Deluxe', 'Magic Fountain (Multi Color)',
                'Peacock Fountain', 'Butterfly Novelty (Flying)', 'Water Queen Fountain', 'Ice Cream Fountain',
                'Siren Fountain', 'Helicopter Novelty', 'Dancing Monkey Novelty', '7 Color Fountain',
                'Pop-Pop Novelty', 'Match Crackers', 'Flash Money Box'
            ],
            'Rockets' => [
                'Baby Rockets', 'Lunik Rockets (Sounding)', 'Whistling Rockets', 'Space Rocket Deluxe',
                'Tri-Color Aerial Rocket', 'Parachute Rocket', 'Multi Shot Rocket 3x', 'Sound & Light Rocket',
                'Mega Titanium Rocket', 'Golden Willow Rocket'
            ],
            'Sound Crackers' => [
                '2/8" Lakshmi Crackers', '3/5" Lakshmi Crackers', '4" Lakshmi Crackers (Big)', '4" Deluxe Sound Crackers',
                '2 3/4" Kuruvi Crackers', '3 1/2" Deluxe Sound Crackers', '5" Mega Sound Bombs', 'Red Fort Sound Crackers'
            ],
            'Fancy Aerial Shots' => [
                '12 Shot Royal Aerial', '25 Shot Sky Show', '30 Shot Night Sparkle', '60 Shot Grand Finale',
                '100 Shot Mega Display', '120 Shot Sky Fireworks', '240 Shot Masterpiece', 'Golden Willow Aerial 30s',
                'Red & Green Comet 25s', 'Silver Brocade Aerial 50s'
            ],
            'Single & Multi Fancy Shots' => [
                'Chotta Fancy', '2" Single Fancy', '2" Fancy (3 Pcs)', '3 1/2" Single Fancy',
                '4 1/2" Single Fancy', '5" Fancy (2 Pcs)', '6" Titan Fancy Shot'
            ],
            'Continuous Sky Shots' => [
                'Air Gel (10 Pcs)', 'Penta (5 Pcs)', '7 Shots (5 Pcs)', '12 Shots Deluxe',
                '15 Shots Sound & Light', '30 Shots Symphony', '60 Shots Magic Sky', '120 Shots Spectacle',
                '240 Shots Ultimate Sky Fest'
            ],
            'Garland & Wala Crackers' => [
                '100 Wala Deluxe', '200 Wala Deluxe', '600 Wala Deluxe', '1000 Wala Mega Garland',
                '2000 Wala Grand Garland', '5000 Wala Royal Garland', '10000 Wala Festival King Garland'
            ],
            'Gift Boxes & Combos' => [
                'Anand 20-Items Gift Box', 'Deepavali Special 35-Items Box', 'Family Delight 50-Items Box',
                'Royal Premium 75-Items Box', 'Aathisha Royal Premium 100-Items Box', 'Mega Jumbo 150-Items Box'
            ]
        ];

        $productCount = 0;
        $sortOrder = 1;

        foreach ($categoriesData as $categoryName => $sampleNames) {
            $category = Category::create([
                'name' => $categoryName,
                'slug' => \Illuminate\Support\Str::slug($categoryName),
                'sort_order' => $sortOrder++,
                'status' => 'active',
            ]);

            $itemsToGenerate = 18; 
            if ($sortOrder <= 3) {
                $itemsToGenerate = 19;
            }

            for ($i = 1; $i <= $itemsToGenerate; $i++) {
                $productCount++;
                $baseName = $sampleNames[($i - 1) % count($sampleNames)];
                $name = $i > count($sampleNames) ? "$baseName Vol. " . ceil($i / count($sampleNames)) : $baseName;

                $mrp = rand(10, 500) * 10;
                $sellingPrice = round($mrp * 0.10, 2);

                Product::create([
                    'category_id' => $category->id,
                    'product_code' => (string) $productCount,
                    'name' => $name,
                    'pack_size' => rand(1, 3) > 1 ? '1 Box (' . rand(1, 10) . ' Pcs)' : '1 Packet (' . rand(5, 10) . ' Pcs)',
                    'mrp' => $mrp,
                    'selling_price' => $sellingPrice,
                    'sort_order' => $productCount,
                    'status' => 'active',
                    'is_bestseller' => rand(0, 1) ? true : false,
                    'stock_quantity' => 100,
                    'manage_stock' => 'no',
                    'stock_status' => 'in_stock',
                ]);
            }
        }

        echo "Successfully seeded $productCount products into [$dbName]!\n";
    } catch (\Exception $e) {
        echo "Error seeding database [$dbName]: " . $e->getMessage() . "\n";
    }
}
