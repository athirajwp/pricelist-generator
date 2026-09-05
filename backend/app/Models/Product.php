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
        'name_ta',
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

    /**
     * Sort a collection or array of products by product_code (S.No / Code) naturally.
     */
    public static function sortCollection($products)
    {
        if (is_null($products)) {
            return collect();
        }

        $collection = is_array($products) ? collect($products) : $products;

        return $collection->sort(function ($a, $b) {
            $codeA = is_object($a) ? ($a->product_code ?? '') : ($a['product_code'] ?? '');
            $codeB = is_object($b) ? ($b->product_code ?? '') : ($b['product_code'] ?? '');
            $codeA = trim((string)$codeA);
            $codeB = trim((string)$codeB);

            if ($codeA !== '' && $codeB !== '') {
                if (is_numeric($codeA) && is_numeric($codeB)) {
                    $numA = (float)$codeA;
                    $numB = (float)$codeB;
                    if ($numA !== $numB) {
                        return $numA <=> $numB;
                    }
                } else {
                    $cmp = strnatcasecmp($codeA, $codeB);
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                }
            } elseif ($codeA !== '') {
                return -1;
            } elseif ($codeB !== '') {
                return 1;
            }

            $sortA = (int)(is_object($a) ? ($a->sort_order ?? 0) : ($a['sort_order'] ?? 0));
            $sortB = (int)(is_object($b) ? ($b->sort_order ?? 0) : ($b['sort_order'] ?? 0));
            if ($sortA !== $sortB) {
                return $sortA <=> $sortB;
            }

            $idA = (int)(is_object($a) ? ($a->id ?? 0) : ($a['id'] ?? 0));
            $idB = (int)(is_object($b) ? ($b->id ?? 0) : ($b['id'] ?? 0));
            return $idA <=> $idB;
        })->values();
    }

    /**
     * Sort categories by the minimum product_code (S.No / Code) of their products.
     */
    public static function sortCategoriesByProductCode($categories)
    {
        if (is_null($categories)) {
            return collect();
        }

        $collection = is_array($categories) ? collect($categories) : $categories;

        return $collection->sort(function ($catA, $catB) {
            $productsA = is_object($catA) ? ($catA->products ?? []) : ($catA['products'] ?? []);
            $productsB = is_object($catB) ? ($catB->products ?? []) : ($catB['products'] ?? []);

            $minA = self::getMinProductCode($productsA);
            $minB = self::getMinProductCode($productsB);

            if ($minA !== $minB) {
                return $minA <=> $minB;
            }

            $sortA = (int)(is_object($catA) ? ($catA->sort_order ?? 0) : ($catA['sort_order'] ?? 0));
            $sortB = (int)(is_object($catB) ? ($catB->sort_order ?? 0) : ($catB['sort_order'] ?? 0));
            if ($sortA !== $sortB) {
                return $sortA <=> $sortB;
            }

            $idA = (int)(is_object($catA) ? ($catA->id ?? 0) : ($catA['id'] ?? 0));
            $idB = (int)(is_object($catB) ? ($catB->id ?? 0) : ($catB['id'] ?? 0));
            return $idA <=> $idB;
        })->values();
    }

    private static function getMinProductCode($products)
    {
        if (empty($products)) {
            return 999999;
        }

        $min = 999999;
        foreach ($products as $p) {
            $code = is_object($p) ? ($p->product_code ?? '') : ($p['product_code'] ?? '');
            $code = trim((string)$code);
            if ($code !== '' && is_numeric($code)) {
                $val = (float)$code;
                if ($val < $min) {
                    $min = $val;
                }
            }
        }
        return $min;
    }
}
