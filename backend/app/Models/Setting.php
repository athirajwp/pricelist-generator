<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Retrieve a setting value statically.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if ($setting) {
            if ($setting->type === 'number') {
                return (float) $setting->value;
            }
            return $setting->value;
        }
        
        // Fallback to environment variables if database settings aren't populated yet
        $envMap = [
            'min_order_value' => 'MIN_ORDER_VALUE',
            'discount_percent' => 'DEFAULT_DISCOUNT_PERCENT',
            'store_whatsapp' => 'STORE_WHATSAPP',
            'store_phone' => 'STORE_PHONE',
            'store_email' => 'STORE_EMAIL',
            'store_address' => 'STORE_ADDRESS',
            'store_upi' => 'STORE_UPI_ID',
            'bank_name' => 'STORE_BANK_NAME',
            'bank_acc_no' => 'STORE_BANK_ACC_NO',
            'bank_ifsc' => 'STORE_BANK_IFSC',
            'bank_holder' => 'STORE_BANK_HOLDER',
        ];

        if (array_key_exists($key, $envMap)) {
            return env($envMap[$key], $default);
        }

        return $default;
    }

    /**
     * Set a setting value statically.
     */
    public static function set(string $key, $value, string $type = 'text')
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}
