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
        Schema::create('order_items', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('order_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('product_id')->constrained()->cascadeOnDelete();
            $blueprint->string('product_name');
            $blueprint->string('pack_size');
            $blueprint->decimal('price', 10, 2); // discounted price at order time
            $blueprint->integer('quantity');
            $blueprint->decimal('total_price', 10, 2);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
