<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $connection = 'central';
    protected $table = 'companies';


    protected $fillable = [
        // Main
        'code', 'name', 'website', 'gst_number', 'pan_number', 'msme_number', 'theme', 'type', 'status', 'tagline',
        
        // Contacts
        'contact_1', 'contact_2', 'contact_3', 'contact_4', 'contact_5',
        'email_1', 'email_2',
        'address_1', 'address_2',
        'state', 'city', 'pincode', 'map_link',

        // Bank Account 1
        'bank_qr_1', 'bank_name_1', 'bank_ifsc_1', 'bank_acc_1', 'bank_branch_1', 'bank_type_1', 'bank_holder_1',
        
        // Bank Account 2
        'bank_qr_2', 'bank_name_2', 'bank_ifsc_2', 'bank_acc_2', 'bank_branch_2', 'bank_type_2', 'bank_holder_2',
        
        // Bank Account 3
        'bank_qr_3', 'bank_name_3', 'bank_ifsc_3', 'bank_acc_3', 'bank_branch_3', 'bank_type_3', 'bank_holder_3',

        // Promos 1-5
        'promo_code_1', 'promo_value_1',
        'promo_code_2', 'promo_value_2',
        'promo_code_3', 'promo_value_3',
        'promo_code_4', 'promo_value_4',
        'promo_code_5', 'promo_value_5',

        // SMTP
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_ssl',

        // SMS
        'sms_header', 'sms_apikey', 'sms_balance',

        // Other Info
        'min_purchase', 'tax_calc', 'delivery_calc',

        // Website overview
        'fb_link', 'tw_link', 'yt_link', 'wa_link', 'ig_link', 'pin_link', 'copyright_text',
        'logo_path', 'favicon_path', 'logo_icon'
    ];
}
