<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'product_code',
        'name',
        'pack_size',
        'mrp',
        'selling_price',
        'image',
        'sort_order',
        'status',
        'is_bestseller',
        'stock_quantity',
        'min_stock_alert',
        'manage_stock',
        'stock_status',
    ];

    protected $casts = [
        'is_bestseller' => 'boolean',
        'stock_quantity' => 'integer',
        'min_stock_alert' => 'integer',
    ];

    /**
     * Automatically update stock status based on stock quantity and alert threshold.
     */
    public function updateStockStatus(): void
    {
        if ($this->manage_stock === 'no') {
            $this->stock_status = 'in_stock';
            return;
        }

        if ($this->stock_quantity <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->stock_quantity <= $this->min_stock_alert) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }
    }

    /**
     * Get product's category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Accessor for calculated discount percentage.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if ($this->mrp <= 0) {
            return 0;
        }
        $discount = (($this->mrp - $this->selling_price) / $this->mrp) * 100;
        return (int) round($discount);
    }
}
