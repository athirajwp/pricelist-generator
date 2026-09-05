<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Display the public price list / quick order storefront.
     */
    public function index()
    {
        $categories = Category::active()
            ->with(['products' => function ($query) {
                $query->active();
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($categories as $cat) {
            $cat->setRelation('products', Product::sortCollection($cat->products));
        }
        $categories = Product::sortCategoriesByProductCode($categories);

        // Load specific configuration settings
        $settings = [
            'min_order_value' => Setting::get('min_order_value', 3800),
            'discount_percent' => Setting::get('discount_percent', 60),
            'store_whatsapp' => Setting::get('store_whatsapp', '919998887776'),
            'store_phone' => Setting::get('store_phone', '+91 9998887776'),
            'store_email' => Setting::get('store_email', 'crackerdemo@gmail.com'),
            'store_address' => Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi'),
            
            // Feature configurations
            'enable_min_order' => Setting::get('enable_min_order', 'yes'),
            'enable_promo_codes' => Setting::get('enable_promo_codes', 'yes'),
            'enable_tax_delivery' => Setting::get('enable_tax_delivery', 'no'),
            'tax_percent' => Setting::get('tax_percent', 18),
            'delivery_charge' => Setting::get('delivery_charge', 150),

            // Promo codes for front-end validation
            'promo_code_1' => Setting::get('promo_code_1', ''),
            'promo_value_1' => Setting::get('promo_value_1', ''),
            'promo_code_2' => Setting::get('promo_code_2', ''),
            'promo_value_2' => Setting::get('promo_value_2', ''),
            'promo_code_3' => Setting::get('promo_code_3', ''),
            'promo_value_3' => Setting::get('promo_value_3', ''),
            'promo_code_4' => Setting::get('promo_code_4', ''),
            'promo_value_4' => Setting::get('promo_value_4', ''),
            'promo_code_5' => Setting::get('promo_code_5', ''),
            'promo_value_5' => Setting::get('promo_value_5', ''),
        ];

        return view('storefront', compact('categories', 'settings'));
    }

    /**
     * Display the official printable price list page.
     */
    public function priceList()
    {
        $categories = Category::active()
            ->with(['products' => function ($query) {
                $query->active();
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($categories as $cat) {
            $cat->setRelation('products', Product::sortCollection($cat->products));
        }
        $categories = Product::sortCategoriesByProductCode($categories);

        $settings = [
            'discount_percent' => Setting::get('discount_percent', 60),
            'store_phone' => Setting::get('store_phone', '+91 9998887776'),
            'store_email' => Setting::get('store_email', 'crackerdemo@gmail.com'),
            'store_address' => Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi'),
        ];

        return view('price_list', compact('categories', 'settings'));
    }
}
