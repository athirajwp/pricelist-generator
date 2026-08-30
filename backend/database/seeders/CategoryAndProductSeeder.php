<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryAndProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Determine active company from database name
        $dbConnName = \Illuminate\Support\Facades\DB::getDefaultConnection();
        $dbName = config("database.connections.{$dbConnName}.database");
        
        $company = null;
        if ($dbName && str_starts_with($dbName, 'crackers2_')) {
            $code = str_replace('crackers2_', '', $dbName);
            try {
                $company = \Illuminate\Support\Facades\DB::connection('central')
                    ->table('companies')
                    ->where('code', $code)
                    ->first();
            } catch (\Exception $e) {
                // Central DB or connection not ready
            }
        }

        // 2. Set dynamic parameters derived from central record or defaults
        $storeName = $company ? $company->name : 'Cracker Demo';
        $storePhone = $company ? ($company->contact_1 ?: '+91 9998887776') : '+91 9998887776';
        $storeEmail = $company ? ($company->email_1 ?: 'crackerdemo@gmail.com') : 'crackerdemo@gmail.com';
        $storeAddress = $company ? ($company->address_1 ?: 'Virudhunagar to Sivakasi Main Road, Sivakasi') : 'Virudhunagar to Sivakasi Main Road, Sivakasi';
        
        $storeWhatsapp = '919998887776';
        if ($company) {
            $wa = $company->wa_link ?: $company->contact_1;
            if ($wa) {
                $cleanWa = preg_replace('/[^0-9]/', '', $wa);
                if (!empty($cleanWa)) {
                    $storeWhatsapp = $cleanWa;
                }
            }
        }

        $bankName = $company ? ($company->bank_name_1 ?: 'State Bank of India') : 'State Bank of India';
        $bankAccNo = $company ? ($company->bank_acc_1 ?: '1234567890') : '1234567890';
        $bankIfsc = $company ? ($company->bank_ifsc_1 ?: 'SBIN0000123') : 'SBIN0000123';
        $bankHolder = $company ? ($company->bank_holder_1 ?: $storeName) : 'Cracker Demo';
        $minPurchase = $company ? ($company->min_purchase ?: '3800') : '3800';

        $settings = [
            'store_name' => ['value' => $storeName, 'type' => 'text'],
            'min_order_value' => ['value' => $minPurchase, 'type' => 'number'],
            'discount_percent' => ['value' => '60', 'type' => 'number'],
            'store_whatsapp' => ['value' => $storeWhatsapp, 'type' => 'text'],
            'store_phone' => ['value' => $storePhone, 'type' => 'text'],
            'store_email' => ['value' => $storeEmail, 'type' => 'text'],
            'store_address' => ['value' => $storeAddress, 'type' => 'textarea'],
            'store_upi' => ['value' => $company ? ($company->bank_acc_1 . '@okaxis') : 'aathishacrackers@okaxis', 'type' => 'text'],
            'bank_name' => ['value' => $bankName, 'type' => 'text'],
            'bank_acc_no' => ['value' => $bankAccNo, 'type' => 'text'],
            'bank_ifsc' => ['value' => $bankIfsc, 'type' => 'text'],
            'bank_holder' => ['value' => $bankHolder, 'type' => 'text'],
            'slider_image_1' => ['value' => 'img/slider img/20631.jpg', 'type' => 'text'],
            'slider_image_2' => ['value' => 'img/slider img/21281.jpg', 'type' => 'text'],
            'slider_image_3' => ['value' => 'img/slider img/21664.jpg', 'type' => 'text'],
            'aboutus_image_1' => ['value' => 'img/about us/image_a58a8943.png', 'type' => 'text'],
            'gallery_image_1' => ['value' => 'img/gallery/about.jpg', 'type' => 'text'],
            'gallery_image_2' => ['value' => 'img/gallery/fire-cracker-shop-R61J4A.jpg', 'type' => 'text'],
            'gallery_image_3' => ['value' => 'img/gallery/images (1).jpg', 'type' => 'text'],
            'gallery_image_4' => ['value' => 'img/gallery/images (2).jpg', 'type' => 'text'],
            'gallery_image_5' => ['value' => 'img/gallery/images (3).jpg', 'type' => 'text'],
            'gallery_image_6' => ['value' => 'img/gallery/images (4).jpg', 'type' => 'text'],
            'gallery_image_7' => ['value' => 'img/gallery/images (5).jpg', 'type' => 'text'],
            'gallery_image_8' => ['value' => 'img/gallery/images (6).jpg', 'type' => 'text'],
            'gallery_image_9' => ['value' => 'img/gallery/images (7).jpg', 'type' => 'text'],
            'gallery_image_10' => ['value' => 'img/gallery/images.jpg', 'type' => 'text'],
        ];

        // Seed promotional codes if defined in company record
        if ($company) {
            for ($i = 1; $i <= 5; $i++) {
                $codeField = "promo_code_{$i}";
                $valField = "promo_value_{$i}";
                if (!empty($company->{$codeField})) {
                    $settings[$codeField] = ['value' => $company->{$codeField}, 'type' => 'text'];
                }
                if (!empty($company->{$valField})) {
                    $settings[$valField] = ['value' => $company->{$valField}, 'type' => 'text'];
                }
            }
            // Seed socials
            $socialMap = [
                'facebook_link' => 'fb_link',
                'twitter_link' => 'tw_link',
                'youtube_link' => 'yt_link',
                'whatsapp_link' => 'wa_link',
                'instagram_link' => 'ig_link',
            ];
            foreach ($socialMap as $settingKey => $compField) {
                if (!empty($company->{$compField})) {
                    $settings[$settingKey] = ['value' => $company->{$compField}, 'type' => 'text'];
                }
            }
            // Seed theme and banners
            if (!empty($company->theme)) {
                $settings['admin_theme'] = ['value' => $company->theme === 'Theme_1' ? 'gold' : ($company->theme === 'Theme_2' ? 'blue' : ($company->theme === 'Theme_3' ? 'emerald' : 'gold')), 'type' => 'text'];
            }
            if (!empty($company->tagline)) {
                $settings['banner_scroller'] = ['value' => $company->tagline, 'type' => 'text'];
            }
        }

        foreach ($settings as $key => $data) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $data['value'], 'type' => $data['type']]
            );
        }


        // 2. Seed Categories and Products
        $inventory = [
            'Sparklers' => [
                'sort_order' => 1,
                'products' => [
                    ['name' => '7cm Electric Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 150.00, 'image' => 'img/7 cm electric sparklers.jpg'],
                    ['name' => '7cm Green Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 160.00, 'image' => 'img/7 cm green sparklers.webp'],
                    ['name' => '7cm Red Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 180.00, 'image' => 'img/7 cm red sparklers.jpg'],
                    ['name' => '10cm Electric Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 240.00, 'image' => 'img/10cm electric sparklers.jpg'],
                    ['name' => '10cm Green Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 260.00, 'image' => 'img/10cm green sparklers.jpg'],
                    ['name' => '10cm Red Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 280.00, 'image' => 'img/10cm red sparklers.jpg'],
                    ['name' => '12cm Electric Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 300.00, 'image' => 'img/12cm electric sparklers.webp'],
                    ['name' => '15cm Multi-Color Sparklers', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 450.00, 'image' => 'img/15Cm Colour Sparklers.jpg'],
                    ['name' => '30cm Electric Sparklers', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 900.00, 'image' => 'img/30cm electric sparklers.jpg'],
                    ['name' => '30cm Color Sparklers', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 1000.00, 'image' => 'img/30cm colour sparklers.jpg'],
                ]
            ],
            'Ground Chakkars' => [
                'sort_order' => 2,
                'products' => [
                    ['name' => 'Ground Chakkars Baby', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 120.00, 'image' => 'img/Ground Chakkars Baby.webp'],
                    ['name' => 'Ground Chakkars Big', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 180.00, 'image' => 'img/Ground Chakkars Big.webp'],
                    ['name' => 'Ground Chakkars Special', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 240.00, 'image' => 'img/Ground Chakkars Special.jpg'],
                    ['name' => 'Ground Chakkars Deluxe', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 320.00, 'image' => 'img/Ground Chakkars Deluxe.webp'],
                    ['name' => 'Disco Wheel (Spinning)', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 400.00, 'image' => 'img/Disco Wheel (Spinning).jpg'],
                ]
            ],
            'Flower Pots' => [
                'sort_order' => 3,
                'products' => [
                    ['name' => 'Flower Pots Small', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 160.00, 'image' => 'img/Flower Pots Small.jpg'],
                    ['name' => 'Flower Pots Big', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 240.00, 'image' => 'img/Flower Pots Big.jpg'],
                    ['name' => 'Flower Pots Special', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 360.00, 'image' => 'img/Flower Pots Special.jpg'],
                    ['name' => 'Flower Pots Deluxe', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 480.00, 'image' => 'img/Flower Pots Deluxe.jpg'],
                    ['name' => 'Flower Pots Giant', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 600.00, 'image' => 'img/Flower Pots Giant.webp'],
                    ['name' => 'Flower Pots Ashoora (Tri-color)', 'pack_size' => '1 Box (2 Pcs)', 'mrp' => 800.00, 'image' => 'img/Flower Pots Ashoora (Tri-color).jpg'],
                ]
            ],
            'Fountains & Novelties' => [
                'sort_order' => 4,
                'products' => [
                    ['name' => 'Twinkling Stars', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 150.00],
                    ['name' => 'Pencil Small', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 160.00],
                    ['name' => 'Pencil Deluxe', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 320.00],
                    ['name' => 'Magic Fountain (Multi Color)', 'pack_size' => '1 Box (2 Pcs)', 'mrp' => 500.00, 'image' => 'img/Magic Fountain (Multi Color).jpg'],
                    ['name' => 'Peacock Fountain', 'pack_size' => '1 Box (1 Pc)', 'mrp' => 600.00],
                    ['name' => 'Butterfly Novelty (Flying)', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 450.00, 'image' => 'img/Butterfly Novelty (Flying).jpg'],
                ]
            ],
            'Rockets' => [
                'sort_order' => 5,
                'products' => [
                    ['name' => 'Baby Rockets', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 180.00],
                    ['name' => 'Lunik Rockets (Sounding)', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 280.00],
                    ['name' => 'Whistling Rockets', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 400.00],
                    ['name' => 'Space Rocket Deluxe', 'pack_size' => '1 Box (3 Pcs)', 'mrp' => 700.00],
                ]
            ],
            'Sound Crackers' => [
                'sort_order' => 6,
                'products' => [
                    ['name' => '2/8\" Lakshmi Crackers', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 30.00],
                    ['name' => '3/5\" Lakshmi Crackers', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 40.00],
                    ['name' => '4\" Lakshmi Crackers (Big)', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 60.00],
                    ['name' => '4\" Deluxe Lakshmi', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 80.00],
                    ['name' => '5\" Super Deluxe Lakshmi', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 120.00],
                    ['name' => 'Bullet Crackers', 'pack_size' => '1 Packet (10 Pcs)', 'mrp' => 100.00],
                ]
            ],
            'Fancy Aerial Shots' => [
                'sort_order' => 7,
                'products' => [
                    ['name' => '7 Shot Crackers', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 350.00],
                    ['name' => '12 Shot Chhota Fancy', 'pack_size' => '1 Box (1 Pc)', 'mrp' => 450.00],
                    ['name' => '30 Shot Multi-Color', 'pack_size' => '1 Box (1 Pc)', 'mrp' => 1200.00],
                    ['name' => '60 Shot Royal Aerial', 'pack_size' => '1 Box (1 Pc)', 'mrp' => 2400.00],
                    ['name' => '120 Shot Grand Finale', 'pack_size' => '1 Box (1 Pc)', 'mrp' => 4800.00],
                ]
            ],
            'Bombs' => [
                'sort_order' => 8,
                'products' => [
                    ['name' => 'Hydro Bomb Green', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 200.00],
                    ['name' => 'Atom Bomb Classic', 'pack_size' => '1 Box (10 Pcs)', 'mrp' => 280.00],
                    ['name' => 'King Kong Bomb (Super Loud)', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 400.00],
                    ['name' => 'Classic Paper Bomb', 'pack_size' => '1 Box (5 Pcs)', 'mrp' => 350.00],
                ]
            ],
            'Gift Boxes' => [
                'sort_order' => 9,
                'products' => [
                    ['name' => 'Anand 20-Items Gift Box', 'pack_size' => '1 Box', 'mrp' => 1200.00],
                    ['name' => 'Deepavali Special 35-Items Box', 'pack_size' => '1 Box', 'mrp' => 2500.00],
                    ['name' => 'Family Delight 50-Items Box', 'pack_size' => '1 Box', 'mrp' => 4000.00],
                    ['name' => 'Aathisha Royal Premium 75-Items Box', 'pack_size' => '1 Box', 'mrp' => 6500.00],
                ]
            ],
        ];

        $discountPercent = Setting::get('discount_percent', 60);

        if (!\Illuminate\Support\Facades\Schema::hasColumn('products', 'product_code')) {
            try {
                \Illuminate\Support\Facades\Schema::table('products', function ($table) {
                    $table->string('product_code')->nullable();
                });
            } catch (\Exception $e) {}
        }

        $codeCounter = 1;
        foreach ($inventory as $catName => $catDetails) {
            // Create Category
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($catName)],
                [
                    'name' => $catName,
                    'sort_order' => $catDetails['sort_order'],
                    'status' => 'active'
                ]
            );

            // Create Products in Category
            foreach ($catDetails['products'] as $prod) {
                // Selling price is calculated based on the general discount percent (e.g. 60% off printed MRP)
                $sellingPrice = $prod['mrp'] * (1 - ($discountPercent / 100));

                $existingProd = Product::where('category_id', $category->id)->where('name', $prod['name'])->first();

                Product::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $prod['name']
                    ],
                    [
                        'product_code' => ($existingProd && !empty($existingProd->product_code)) ? $existingProd->product_code : (string) $codeCounter,
                        'pack_size' => $prod['pack_size'],
                        'mrp' => $prod['mrp'],
                        'selling_price' => $sellingPrice,
                        'image' => $prod['image'] ?? null,
                        'status' => 'active'
                    ]
                );
                $codeCounter++;
            }
        }

        // Auto-assign product codes to any existing products missing a code
        $missingCodeProds = Product::whereNull('product_code')->orWhere('product_code', '')->get();
        foreach ($missingCodeProds as $mProd) {
            $mProd->update(['product_code' => (string) $codeCounter++]);
        }
    }
}
