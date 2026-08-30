<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'landmark',
        'city',
        'state',
        'pincode',
        'subtotal',
        'discount_amount',
        'net_amount',
        'payment_status',
        'order_status',
        'transport_name',
        'lr_number',
        'notes',
    ];

    /**
     * Auto-generate order number on creation.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ATC-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    /**
     * Get line items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get a color badge class for the order status.
     */
    public function getOrderStatusBadgeAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'bg-amber-500 text-white',
            'approved' => 'bg-sky-500 text-white',
            'processing' => 'bg-blue-600 text-white',
            'shipped' => 'bg-purple-600 text-white',
            'delivered' => 'bg-emerald-600 text-white',
            'cancelled' => 'bg-rose-600 text-white',
            default => 'bg-slate-500 text-white',
        };
    }

    /**
     * Get a color badge class for the payment status.
     */
    public function getPaymentStatusBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'bg-rose-600 text-white',
            'paid' => 'bg-emerald-600 text-white',
            'verified' => 'bg-emerald-600 text-white',
            default => 'bg-slate-500 text-white',
        };
    }
}
