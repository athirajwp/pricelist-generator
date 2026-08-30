<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Order;
use Illuminate\Http\Request;

class StorefrontApiController extends Controller
{
    /**
     * Get categories, active products, and store settings.
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
            $sortedProducts = $cat->products->sort(function ($a, $b) {
                $codeA = (is_numeric($a->product_code) && intval($a->product_code) > 0) ? intval($a->product_code) : 99999;
                $codeB = (is_numeric($b->product_code) && intval($b->product_code) > 0) ? intval($b->product_code) : 99999;
                if ($codeA === $codeB) {
                    return strcmp($a->name, $b->name);
                }
                return $codeA <=> $codeB;
            })->values();
            $cat->setRelation('products', $sortedProducts);
        }

        $company = view()->shared('currentCompany');

        $storeName = Setting::get('store_name', '');
        if (empty($storeName) && $company && !empty($company->name)) {
            $storeName = $company->name;
        }
        if (empty($storeName)) {
            $storeName = 'Cracker Demo';
        }

        $storeLogo = Setting::get('store_logo', '');
        if (empty($storeLogo) && $company && !empty($company->logo_path)) {
            $storeLogo = $company->logo_path;
        }
        if ($storeLogo === 'img/crackers logo.jpg' || $storeLogo === '/img/crackers logo.jpg') {
            $storeLogo = '';
        }

        $storeFavicon = Setting::get('store_favicon', '');
        if (empty($storeFavicon) && $company && !empty($company->favicon_path)) {
            $storeFavicon = $company->favicon_path;
        }

        $settings = [
            'store_name' => $storeName,
            'store_logo' => $storeLogo,
            'store_favicon' => $storeFavicon,
            'min_order_value' => Setting::get('min_order_value', 3800),
            'discount_percent' => Setting::get('discount_percent', 60),
            'store_whatsapp' => Setting::get('store_whatsapp', '919998887776'),
            'store_phone' => Setting::get('store_phone', '+91 9998887776'),
            'store_phone_2' => Setting::get('store_phone_2', ''),
            'store_phone_3' => Setting::get('store_phone_3', ''),
            'store_phone_4' => Setting::get('store_phone_4', ''),
            'store_gpay' => Setting::get('store_gpay', '9787772038'),
            'store_email' => Setting::get('store_email', 'crackerdemo@gmail.com'),
            'store_address' => Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi'),
            'store_invocation' => Setting::get('store_invocation', 'சங்கிலி மாடசாமி துணை, கொமண்டியம்மன் துணை'),
            'store_invocation_symbol' => Setting::get('store_invocation_symbol', 'உ'),
            'store_name_font' => Setting::get('store_name_font', 'cinzel'),
            'store_deity_preset' => Setting::get('store_deity_preset', 'vinayagar'),
            'store_deity_image' => Setting::get('store_deity_image', ''),
            'store_cover_bg' => Setting::get('store_cover_bg', ''),
            'store_logo' => Setting::get('store_logo', ''),
            'store_upi_qr' => Setting::get('store_upi_qr', ''),
            'table_row_height' => Setting::get('table_row_height', '22'),
            'table_col_padding' => Setting::get('table_col_padding', '4'),
            
            // Feature configurations
            'enable_min_order' => Setting::get('enable_min_order', 'yes'),
            'enable_promo_codes' => Setting::get('enable_promo_codes', 'yes'),
            'enable_tax_delivery' => Setting::get('enable_tax_delivery', 'no'),
            'enable_legal_notice' => Setting::get('enable_legal_notice', 'yes'),
            'enable_fireworks' => Setting::get('enable_fireworks', 'yes'),
            'enable_aos' => Setting::get('enable_aos', 'yes'),
            'enable_most_sold' => Setting::get('enable_most_sold', 'yes'),
            'show_mrp' => Setting::get('show_mrp', 'yes'),
            'card_bg_color' => Setting::get('card_bg_color', '#FFFFFF'),
            'admin_theme' => Setting::get('admin_theme', 'theme_1'),
            'default_view_mode' => Setting::get('default_view_mode', 'flex'),
            'tax_percent' => Setting::get('tax_percent', 18),
            'delivery_charge' => Setting::get('delivery_charge', 150),

            // Promo codes
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

            // Image slider / brandings
            'slider_image_1' => Setting::get('slider_image_1', ''),
            'slider_image_2' => Setting::get('slider_image_2', ''),
            'slider_image_3' => Setting::get('slider_image_3', ''),

            // Quick Order banner images
            'quick_order_banner_1' => Setting::get('quick_order_banner_1', ''),
            'quick_order_banner_2' => Setting::get('quick_order_banner_2', ''),
            'quick_order_banner_3' => Setting::get('quick_order_banner_3', ''),

            // Marquee alerts
            'marquee_alert_1' => Setting::get('marquee_alert_1', 'Special Offer: 60% Discount on all items!'),
            'marquee_alert_2' => Setting::get('marquee_alert_2', 'Free Delivery on orders above Rs. 5000!'),
            'marquee_alert_3' => Setting::get('marquee_alert_3', 'Celebrate Diwali / Festivals with Flat <strong>60% Discount</strong>!'),
            'marquee_alert_4' => Setting::get('marquee_alert_4', 'Express Lorry Transport Delivery Across Kerala, Karnataka, Tamilnadu, Andhra & Telangana!'),
            'marquee_alert_5' => Setting::get('marquee_alert_5', 'For Enquiries, Contact Support: <strong>8682942042</strong>'),
            'marquee_alert_6' => Setting::get('marquee_alert_6', '100% Quality & Safe Sivakasi Manufactured Crackers'),

            // Map and Compliance
            'store_map_iframe' => Setting::get('store_map_iframe', ''),
            'license_name' => Setting::get('license_name', 'Jallikattu Crackers'),
            'license_no' => Setting::get('license_no', '123/ABCD/2024'),
            'terms_conditions' => Setting::get('terms_conditions', ''),

            // About Us Customizations
            'about_us' => Setting::get('about_us', "We deliver high quality crackers.\nSafety is our top priority."),
            'about_us_badge' => Setting::get('about_us_badge', 'Sivakasi Pioneers'),
            'about_us_title' => Setting::get('about_us_title', 'Bringing Joy Since 1999'),
            'aboutus_image_1' => Setting::get('aboutus_image_1', ''),

            // Social Media Links
            'facebook_link' => Setting::get('facebook_link', ''),
            'instagram_link' => Setting::get('instagram_link', ''),
            'youtube_link' => Setting::get('youtube_link', ''),
            'whatsapp_link' => Setting::get('whatsapp_link', ''),
            'twitter_link' => Setting::get('twitter_link', ''),
        ];

        $imgDir = public_path('img');
        $scannedMap = [];
        if (file_exists($imgDir)) {
            foreach (scandir($imgDir) as $f) {
                if ($f === '.' || $f === '..' || is_dir($imgDir . '/' . $f)) continue;
                $bName = pathinfo($f, PATHINFO_FILENAME);
                $cleanBName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', str_replace('colour', 'color', $bName)));
                $scannedMap[$cleanBName] = 'img/' . $f;
            }
        }

        foreach ($categories as $cat) {
            foreach ($cat->products as $product) {
                if (empty($product->image)) {
                    $cleanProdName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', str_replace('colour', 'color', $product->name)));
                    if (isset($scannedMap[$cleanProdName])) {
                        $product->setAttribute('image', $scannedMap[$cleanProdName]);
                    } else {
                        foreach ($scannedMap as $key => $path) {
                            if ($key === $cleanProdName || str_contains($cleanProdName, $key) || str_contains($key, $cleanProdName)) {
                                $product->setAttribute('image', $path);
                                break;
                            }
                        }
                    }
                }
            }
        }

        $slider1 = Setting::get('slider_image_1', '');
        if (empty($slider1) && file_exists(public_path('img/slider img/20631.jpg'))) {
            $slider1 = 'img/slider img/20631.jpg';
        }

        $aboutImg1 = Setting::get('aboutus_image_1', '');
        if (empty($aboutImg1) && file_exists(public_path('img/about us/about_showcase.png'))) {
            $aboutImg1 = 'img/about us/about_showcase.png';
        }

        $settings['slider_image_1'] = $slider1;
        $settings['aboutus_image_1'] = $aboutImg1;
        $settings['page_header_banner'] = Setting::get('page_header_banner', '');
        $settings['about_banner'] = Setting::get('about_banner', '');
        $settings['about_banner_1'] = Setting::get('about_banner_1', '');
        $settings['about_banner_2'] = Setting::get('about_banner_2', '');
        $settings['about_banner_3'] = Setting::get('about_banner_3', '');
        $settings['contact_banner'] = Setting::get('contact_banner', '');
        $settings['contact_banner_1'] = Setting::get('contact_banner_1', '');
        $settings['contact_banner_2'] = Setting::get('contact_banner_2', '');
        $settings['contact_banner_3'] = Setting::get('contact_banner_3', '');

        $galleryDir = public_path('img/gallery');
        $galleryFiles = [];
        if (file_exists($galleryDir)) {
            foreach (scandir($galleryDir) as $gf) {
                if ($gf === '.' || $gf === '..' || is_dir($galleryDir . '/' . $gf)) continue;
                $galleryFiles[] = 'img/gallery/' . $gf;
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            $val = Setting::get("gallery_image_{$i}", '');
            if (empty($val) && isset($galleryFiles[$i - 1])) {
                $val = $galleryFiles[$i - 1];
            }
            $settings["gallery_image_{$i}"] = $val;
        }

        return response()->json([
            'categories' => $categories,
            'settings' => $settings,
        ]);
    }

    /**
     * Search orders.
     */
    public function track(Request $request)
    {
        $request->validate([
            'search_query' => 'required|string|max:255',
        ]);

        $query = $request->input('search_query');

        $orders = Order::where('order_number', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('whatsapp', 'like', "%{$query}%")
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
            'query' => $query,
        ]);
    }

    /**
     * Get details for checkout success.
     */
    public function successDetails($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();

        $upiId = Setting::get('store_upi', 'aathishacrackers@okaxis');
        $storeName = Setting::get('store_name', 'Cracker Demo');
        
        $encodedStoreName = urlencode($storeName);
        $upiPayUrl = "upi://pay?pa={$upiId}&pn={$encodedStoreName}&am={$order->net_amount}&cu=INR";
        
        $customQr = Setting::get('store_upi_qr', '');
        if (!empty($customQr) && file_exists(public_path($customQr))) {
            $qrCodeUrl = '/' . $customQr;
        } else {
            $qrCodeUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($upiPayUrl) . "&choe=UTF-8";
        }

        $bankDetails = [
            'name' => Setting::get('bank_name', 'State Bank of India'),
            'acc_no' => Setting::get('bank_acc_no', '1234567890'),
            'ifsc' => Setting::get('bank_ifsc', 'SBIN0000123'),
            'holder' => Setting::get('bank_holder', 'Cracker Demo'),
            'upi_id' => Setting::get('store_upi_id', 'crackerdemo@okaxis'),
        ];

        $whatsappNum = preg_replace('/[^0-9]/', '', Setting::get('store_whatsapp', '919998887776'));

        $waMessage = "Hello " . $storeName . ", I have placed an order!\n\n"
                   . "*Order Number:* {$order->order_number}\n"
                   . "*Customer Name:* {$order->name}\n"
                   . "*Total Amount:* ₹" . number_format($order->net_amount, 2) . "\n\n"
                   . "Please confirm my booking and coordinate delivery details.";
        
        $whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappNum}&text=" . urlencode($waMessage);

        $waMessageCustomer = "Hello *" . $order->name . "*,\n\n"
                           . "Here is the invoice summary for your order at *" . $storeName . "*:\n\n"
                           . "*Order Number:* " . $order->order_number . "\n"
                           . "*Order Date:* " . $order->created_at->format('d M Y, h:i A') . "\n"
                           . "*Net Amount:* ₹" . number_format($order->net_amount, 2) . "\n"
                           . "*Order Status:* " . ucfirst($order->order_status) . "\n"
                           . "*Payment Status:* " . ucfirst($order->payment_status) . "\n\n"
                           . "*Order Items Summary:*\n";
        
        foreach($order->items as $item) {
            $waMessageCustomer .= "• " . $item->product_name . " (Qty: " . $item->quantity . ") - ₹" . number_format($item->total_price, 2) . "\n";
        }
        
        $waMessageCustomer .= "\nTrack your order here: " . url('/track?query=' . $order->order_number) . "\n\n"
                            . "Thank you for booking with us!";
        
        $customerPhone = preg_replace('/[^0-9]/', '', $order->whatsapp ?: $order->phone);
        if (strlen($customerPhone) === 10) {
            $customerPhone = '91' . $customerPhone;
        }
        $customerWhatsappUrl = "https://api.whatsapp.com/send?phone=" . $customerPhone . "&text=" . urlencode($waMessageCustomer);

        return response()->json([
            'order' => $order,
            'qrCodeUrl' => $qrCodeUrl,
            'bankDetails' => $bankDetails,
            'whatsappUrl' => $whatsappUrl,
            'whatsappNum' => $whatsappNum,
            'customerWhatsappUrl' => $customerWhatsappUrl,
        ]);
    }

    /**
     * Export real vector PDF document (Dompdf / Microsoft Word style).
     */
    public function downloadPricelistPdf(Request $request)
    {
        $categories = Category::active()
            ->with(['products' => function ($query) {
                $query->active();
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        $allFilteredProducts = [];
        foreach ($categories as $cat) {
            $sortedProducts = $cat->products->sort(function ($a, $b) {
                $codeA = (is_numeric($a->product_code) && intval($a->product_code) > 0) ? intval($a->product_code) : 99999;
                $codeB = (is_numeric($b->product_code) && intval($b->product_code) > 0) ? intval($b->product_code) : 99999;
                if ($codeA === $codeB) {
                    return strcmp($a->name, $b->name);
                }
                return $codeA <=> $codeB;
            })->values();

            foreach ($sortedProducts as $p) {
                $allFilteredProducts[] = [
                    'id' => $p->id,
                    'product_code' => $p->product_code,
                    'name' => $p->name,
                    'pack_size' => $p->pack_size,
                    'mrp' => $p->mrp,
                    'selling_price' => $p->selling_price,
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                ];
            }
        }

        // TR-Based Chunking (Max 33 TR per page)
        $MAX_TR_PER_PAGE = 33;
        $productPageChunks = [];
        $currentChunkProducts = [];
        $currentChunkTrCount = 0;
        $currentCatIdInChunk = null;

        foreach ($allFilteredProducts as $product) {
            $needsNewCatHeader = $product['category_id'] !== $currentCatIdInChunk;
            $trCostForThisProduct = ($needsNewCatHeader ? 1 : 0) + 1;

            if ($currentChunkTrCount + $trCostForThisProduct > $MAX_TR_PER_PAGE && count($currentChunkProducts) > 0) {
                $productPageChunks[] = $currentChunkProducts;
                $currentChunkProducts = [];
                $currentChunkTrCount = 0;
                $currentCatIdInChunk = null;
            }

            $currentChunkProducts[] = $product;
            if ($product['category_id'] !== $currentCatIdInChunk) {
                $currentChunkTrCount += 1;
                $currentCatIdInChunk = $product['category_id'];
            }
            $currentChunkTrCount += 1;
        }

        if (count($currentChunkProducts) > 0) {
            $productPageChunks[] = $currentChunkProducts;
        }

        $storeName = Setting::get('store_name', 'MASS CRACKERS');
        $storePhone = Setting::get('store_phone', '63837 22887');
        $storeEmail = Setting::get('store_email', 'www.masscrackers.com');
        $storeAddress = Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Opposite to Nayagara Petrol Bulk, Amathur - 626005.');
        $discountPercent = Setting::get('discount_percent', 50);

        $settings = [
            'store_name' => $storeName,
            'store_phone' => $storePhone,
            'store_email' => $storeEmail,
            'store_address' => $storeAddress,
            'discount_percent' => $discountPercent,
        ];

        $editForm = [
            'store_name' => $request->query('store_name', Setting::get('store_name', 'MASS CRACKERS')),
            'store_tagline' => $request->query('store_tagline', Setting::get('store_tagline', 'Ready for the Sparkle')),
            'store_invocation' => $request->query('store_invocation', Setting::get('store_invocation', 'உ • சங்கிலி மாடசாமி துணை • கொமண்டியம்மன் துணை')),
            'store_year' => $request->query('store_year', Setting::get('store_year', date('Y'))),
            'store_email' => $request->query('store_email', Setting::get('store_email', 'www.masscrackers.com')),
            'store_phone' => $request->query('store_phone', Setting::get('store_phone', '63837 22887')),
            'store_phone_2' => $request->query('store_phone_2', Setting::get('store_phone_2', '97877 72038')),
            'store_phone_3' => $request->query('store_phone_3', Setting::get('store_phone_3', '97877 72038')),
            'store_address' => $request->query('store_address', Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Opposite to Nayagara Petrol Bulk, Amathur - 626005.')),
            'discount_percent' => $request->query('discount_percent', Setting::get('discount_percent', 50)),
            'bank_name' => $request->query('bank_name', Setting::get('bank_name', 'Muthusamy Ganesan')),
            'bank_branch' => $request->query('bank_branch', Setting::get('bank_branch', 'IDBI Bank')),
            'bank_account_no' => $request->query('bank_account_no', Setting::get('bank_account_no', '1118104000136815')),
            'bank_ifsc' => $request->query('bank_ifsc', Setting::get('bank_ifsc', 'IBKL0001118')),
            'important_note_1' => $request->query('important_note_1', Setting::get('important_note_1', 'தொடர்ந்து பல ஆண்டுகளாக எங்கள் நிறுவன பட்டாசுகளை வாங்கி தீபாவளியை குடும்பத்தினருடன் கொண்டாடி மகிழும் உங்கள் அனைவருக்கும் இனிய தீபாவளி நல்வாழ்த்துக்கள்!')),
            'important_note_2' => $request->query('important_note_2', Setting::get('important_note_2', 'வரவிருக்கும் தீபாவளி பண்டிகைக்கான பட்டாசுகளை அக்டோபர் 15 - ஆம் தேதிக்குள் ஆர்டர் செய்து பெற்றுக்கொள்ளுமாறு வேண்டுகிறோம்.')),
        ];

        $data = [
            'settings' => $settings,
            'editForm' => $editForm,
            'productPageChunks' => $productPageChunks,
            'discountPercent' => $editForm['discount_percent'],
            'showMrp' => true,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.pricelist', $data);
        $pdf->setPaper('a4', 'portrait');

        $cleanName = preg_replace('/[^a-z0-9]/i', '_', strtolower($editForm['store_name']));
        return $pdf->download("{$cleanName}_price_list_a4.pdf");
    }
}
