<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Show general settings panel.
     */
    public function index()
    {
        $settings = [
            'store_name' => Setting::get('store_name', 'Cracker Demo'),
            'min_order_value' => Setting::get('min_order_value', 3800),
            'discount_percent' => Setting::get('discount_percent', 60),
            'store_whatsapp' => Setting::get('store_whatsapp', '919998887776'),
            'store_phone' => Setting::get('store_phone', '+91 9998887776'),
            'store_email' => Setting::get('store_email', 'crackerdemo@gmail.com'),
            'store_address' => Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi'),
            'store_upi' => Setting::get('store_upi', 'aathishacrackers@okaxis'),
            'store_upi_qr' => Setting::get('store_upi_qr', ''),
            'bank_name' => Setting::get('bank_name', 'State Bank of India'),
            'bank_acc_no' => Setting::get('bank_acc_no', '1234567890'),
            'bank_ifsc' => Setting::get('bank_ifsc', 'SBIN0000123'),
            'bank_holder' => Setting::get('bank_holder', 'Cracker Demo'),
            
            // Feature flags
            'enable_min_order' => Setting::get('enable_min_order', 'yes'),
            'enable_promo_codes' => Setting::get('enable_promo_codes', 'yes'),
            'enable_tax_delivery' => Setting::get('enable_tax_delivery', 'no'),
            'enable_legal_notice' => Setting::get('enable_legal_notice', 'yes'),
            'tax_percent' => Setting::get('tax_percent', 18),
            'delivery_charge' => Setting::get('delivery_charge', 150),
        ];

        return view('admin.settings', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $rules = [
            'store_name' => 'required|string|max:255',
            'min_order_value' => 'required|numeric|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'store_whatsapp' => 'required|string|max:20',
            'store_phone' => 'required|string|max:20',
            'store_email' => 'required|email|max:255',
            'store_address' => 'required|string',
            'store_upi' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_acc_no' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:255',
            'bank_holder' => 'nullable|string|max:255',
            
            // Feature flags validation
            'enable_min_order' => 'required|in:yes,no',
            'enable_promo_codes' => 'required|in:yes,no',
            'enable_tax_delivery' => 'required|in:yes,no',
            'enable_legal_notice' => 'required|in:yes,no',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'delivery_charge' => 'required|numeric|min:0',
            
            // UPI QR validation
            'store_upi_qr' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
        ];

        $request->validate($rules);

        // Update each setting statically
        Setting::set('store_name', $request->store_name, 'text');
        Setting::set('min_order_value', $request->min_order_value, 'number');
        Setting::set('discount_percent', $request->discount_percent, 'number');
        Setting::set('store_whatsapp', $request->store_whatsapp, 'text');
        Setting::set('store_phone', $request->store_phone, 'text');
        Setting::set('store_email', $request->store_email, 'text');
        Setting::set('store_address', $request->store_address, 'textarea');
        Setting::set('store_upi', $request->store_upi ?? '', 'text');
        Setting::set('bank_name', $request->bank_name ?? '', 'text');
        Setting::set('bank_acc_no', $request->bank_acc_no ?? '', 'text');
        Setting::set('bank_ifsc', $request->bank_ifsc ?? '', 'text');
        Setting::set('bank_holder', $request->bank_holder ?? '', 'text');
        
        // Save feature flags
        Setting::set('enable_min_order', $request->enable_min_order, 'text');
        Setting::set('enable_promo_codes', $request->enable_promo_codes, 'text');
        Setting::set('enable_tax_delivery', $request->enable_tax_delivery, 'text');
        Setting::set('enable_legal_notice', $request->enable_legal_notice, 'text');
        Setting::set('tax_percent', $request->tax_percent, 'number');
        Setting::set('delivery_charge', $request->delivery_charge, 'number');

        // Handle file upload
        if ($request->hasFile('store_upi_qr')) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/settings";

            $oldPath = Setting::get('store_upi_qr');
            if ($oldPath && file_exists(public_path($oldPath))) {
                @unlink(public_path($oldPath));
            }

            $fileName = time() . '_store_upi_qr_' . uniqid() . '.' . $request->file('store_upi_qr')->extension();
            $request->file('store_upi_qr')->move(public_path($uploadDir), $fileName);
            $filePath = $uploadDir . '/' . $fileName;

            Setting::set('store_upi_qr', $filePath, 'text');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Store settings updated successfully!');
    }
}
