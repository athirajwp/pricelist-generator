<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    /**
     * Display the site branding customization console.
     */
    public function index()
    {
        $keys = [
            'instagram_link', 'whatsapp_link', 'youtube_link', 'twitter_link', 'facebook_link',
            'promo_code_1', 'promo_value_1',
            'promo_code_2', 'promo_value_2',
            'promo_code_3', 'promo_value_3',
            'promo_code_4', 'promo_value_4',
            'promo_code_5', 'promo_value_5',
            'admin_theme', 'banner_scroller',
            'terms_conditions', 'about_us',
            'about_us_badge', 'about_us_title',
            'slider_image_1', 'slider_image_2', 'slider_image_3',
            'aboutus_image_1',
            'gallery_image_1', 'gallery_image_2', 'gallery_image_3',
            'gallery_image_4', 'gallery_image_5', 'gallery_image_6',
            'gallery_image_7', 'gallery_image_8', 'gallery_image_9',
            'gallery_image_10', 'gallery_image_11', 'gallery_image_12',
            'gallery_image_13', 'gallery_image_14', 'gallery_image_15',
            'gallery_image_16', 'gallery_image_17', 'gallery_image_18',
            'store_address', 'store_phone', 'store_email',
            'bank_name', 'bank_ifsc', 'bank_acc_no', 'bank_holder', 'bank_branch', 'bank_acc_type',
            'license_name', 'license_no', 'store_map_iframe',
            'marquee_alert_1', 'marquee_alert_2', 'marquee_alert_3', 'marquee_alert_4', 'marquee_alert_5', 'marquee_alert_6',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        // Apply fallback default
        if (empty($settings['admin_theme'])) {
            $settings['admin_theme'] = 'gold';
        }

        if (empty($settings['about_us_badge'])) {
            $settings['about_us_badge'] = 'A Decade of Quality';
        }

        if (empty($settings['about_us_title'])) {
            $settings['about_us_title'] = 'We Provide Premium Quality Fireworks';
        }

        if (empty($settings['about_us'])) {
            $settings['about_us'] = '<p><strong>We are a highly reputed and reliable name involved in the field of Fireworks trading business for the past 10 years.</strong></p><p>We offer a wide range of fireworks products such as Sparklers, Ground Chakkars, Twinkling Stars, Chorsa, Rockets, Flower Pots, Pencils, Atom Bombs, Colour Matches, and other Fancy Aerial Items. We also offer standard and customized fireworks gift boxes at highly competitive Sivakasi wholesale prices.</p><p>Through websites, instant WhatsApp checkouts, and modern logistic systems, we are able to process, pack, and ship your orders to Kerala, Karnataka, Andhra, Telangana, and Tamilnadu faster, safer, and on-time to your complete satisfaction.</p>';
        }

        if (empty($settings['terms_conditions'])) {
            $settings['terms_conditions'] = '<h3>1. Booking Eligibility & Order Guidelines</h3><p>By placing an order on our online booking storefront, you confirm that you are at least 18 years of age and authorized to purchase fireworks products in your local jurisdiction.</p><p>All items added to your cart represent Sivakasi wholesale stock and are subject to availability. The minimum purchase value to qualify for transport delivery is strictly <strong>₹3,800</strong> (net payable value after flat discounts are calculated).</p><h3>2. Pricing, Discounts & Wholesale Schemes</h3><p>All products listed indicate their Maximum Retail Price (MRP) alongside our discounted Sivakasi wholesale rate (a standard <strong>60% off</strong>). Prices are subject to change in line with chemical feedstock costs, but prices locked in at order submission remain fully guaranteed.</p><h3>3. Payment & Booking Terms</h3><p>We operate on a booking estimation model. No online payments are accepted on this website due to regulatory guidelines.</p><p>Upon finalizing your checkout, a booking invoice summary is generated. Please click the WhatsApp button to share your booking reference with us. Our representative will contact you to verify details, discuss payment terms, and complete the order processing offline.</p><h3>4. Shipping, Lorry Transports & Delivery</h3><p>In accordance with the Explosives Act of India, firecrackers cannot be delivered via courier services (such as DTDC or BlueDart) or postal mail.</p><p>All bookings are securely packed in heavy-duty wooden boxes and shipped via licensed third-party <strong>Lorry Transport Services</strong> to your nearest transport hub/godown.</p><p>Customers will receive their Lorry Receipt (LR) tracking slip containing booking logs. You are required to collect the goods directly from the transport godown and pay any minor local freight charges upon collection.</p><h3>5. Safety, Liability & Bursting Guidelines</h3><p>Firecrackers are inherently chemical materials. Please exercise extreme caution when storing and igniting. Always supervise children, wear cotton garments, keep a bucket of water adjacent to the firing area, and follow state safety guidelines.</p>';
        }

        return view('admin.branding', compact('settings'));
    }

    /**
     * Update branding parameters and dynamic image assets.
     */
    public function update(Request $request)
    {
        // 1. Handle all text / text-area input fields
        $fields = $request->except([
            '_token', 'slider_image_1', 'slider_image_2', 'slider_image_3', 'aboutus_image_1',
            'gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5', 'gallery_image_6',
            'gallery_image_7', 'gallery_image_8', 'gallery_image_9', 'gallery_image_10', 'gallery_image_11', 'gallery_image_12',
            'gallery_image_13', 'gallery_image_14', 'gallery_image_15', 'gallery_image_16', 'gallery_image_17', 'gallery_image_18'
        ]);

        foreach ($fields as $key => $value) {
            $type = 'text';
            if (in_array($key, ['terms_conditions', 'about_us', 'store_map_iframe'])) {
                $type = 'textarea';
            }
            Setting::set($key, $value, $type);
        }

        // Sync theme with the central Company model record
        if ($request->has('theme')) {
            $company = view()->shared('currentCompany');
            if ($company) {
                $company->update(['theme' => $request->theme]);
            }
        }


        // 2. Handle all file uploads dynamically
        $imageFields = [
            'slider_image_1', 'slider_image_2', 'slider_image_3',
            'aboutus_image_1',
            'gallery_image_1', 'gallery_image_2', 'gallery_image_3',
            'gallery_image_4', 'gallery_image_5', 'gallery_image_6',
            'gallery_image_7', 'gallery_image_8', 'gallery_image_9',
            'gallery_image_10', 'gallery_image_11', 'gallery_image_12',
            'gallery_image_13', 'gallery_image_14', 'gallery_image_15',
            'gallery_image_16', 'gallery_image_17', 'gallery_image_18',
        ];

        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';
        $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
        $uploadDir = "uploads/companies/{$companyCodeClean}/branding";

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $request->validate([
                    $field => 'image|mimes:jpeg,png,jpg,webp|max:20480'
                ]);

                $oldPath = Setting::get($field);
                if ($oldPath && file_exists(public_path($oldPath))) {
                    @unlink(public_path($oldPath));
                }

                $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $request->file($field)->extension();
                $request->file($field)->move(public_path($uploadDir), $fileName);
                $filePath = $uploadDir . '/' . $fileName;

                Setting::set($field, $filePath, 'text');
            }
        }

        return redirect()->route('admin.branding.index')->with('success', 'Site branding customizations updated successfully!');
    }
}
