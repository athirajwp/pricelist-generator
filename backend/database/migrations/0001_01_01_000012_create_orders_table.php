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
        Schema::create('orders', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('order_number')->unique();
            $blueprint->string('name');
            $blueprint->string('phone');
            $blueprint->string('whatsapp')->nullable();
            $blueprint->string('email')->nullable();
            $blueprint->text('address');
            $blueprint->string('landmark')->nullable();
            $blueprint->string('city');
            $blueprint->string('state');
            $blueprint->string('pincode');
            $blueprint->decimal('subtotal', 10, 2); // original MRP sum
            $blueprint->decimal('discount_amount', 10, 2); // original MRP - discounted sum
            $blueprint->decimal('net_amount', 10, 2); // actual payable amount
            $blueprint->string('payment_status')->default('pending'); // pending, paid, verified
            $blueprint->string('order_status')->default('pending'); // pending, approved, processing, shipped, delivered, cancelled
            $blueprint->string('transport_name')->nullable(); // lorry transport name
            $blueprint->string('lr_number')->nullable(); // lorry receipt number
            $blueprint->text('notes')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
