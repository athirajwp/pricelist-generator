<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(100)->after('selling_price');
            }
            if (!Schema::hasColumn('products', 'min_stock_alert')) {
                $table->integer('min_stock_alert')->default(10)->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'manage_stock')) {
                $table->string('manage_stock')->default('yes')->after('min_stock_alert');
            }
            if (!Schema::hasColumn('products', 'stock_status')) {
                $table->string('stock_status')->default('in_stock')->after('manage_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'min_stock_alert', 'manage_stock', 'stock_status']);
        });
    }
};
