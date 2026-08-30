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
        Schema::create('products', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('category_id')->constrained()->cascadeOnDelete();
            $blueprint->string('name');
            $blueprint->string('pack_size')->default('1 Box'); // e.g. "10 Pieces", "1 Box", "5 Pcs"
            $blueprint->decimal('mrp', 10, 2); // original price
            $blueprint->decimal('selling_price', 10, 2); // discounted price
            $blueprint->string('image')->nullable();
            $blueprint->string('status')->default('active'); // active, inactive
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
