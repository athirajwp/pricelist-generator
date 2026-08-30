<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'product_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('product_code')->nullable()->after('category_id');
            });
        }

        // Auto-assign product codes to all existing products starting from 101
        $products = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order', 'asc')
            ->orderBy('products.sort_order', 'asc')
            ->orderBy('products.id', 'asc')
            ->select('products.id', 'products.product_code')
            ->get();

        $codeNum = 1;
        foreach ($products as $prod) {
            if (empty($prod->product_code)) {
                DB::table('products')
                    ->where('id', $prod->id)
                    ->update(['product_code' => (string) $codeNum]);
                $codeNum++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'product_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('product_code');
            });
        }
    }
};
