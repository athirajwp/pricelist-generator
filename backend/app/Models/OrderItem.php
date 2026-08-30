<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'pack_size',
        'price',
        'quantity',
        'total_price',
    ];

    /**
     * Get parent order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get original product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
