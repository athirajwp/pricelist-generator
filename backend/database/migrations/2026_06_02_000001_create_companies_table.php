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
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name');
                $table->string('website')->nullable();
                $table->string('contact_1')->nullable();
                $table->string('contact_2')->nullable();
                $table->string('contact_3')->nullable();
                $table->text('address')->nullable();
                $table->string('gst_no')->nullable();
                $table->string('pan_no')->nullable();
                $table->string('msme_no')->nullable();
                $table->string('bank_name_1')->nullable();
                $table->string('bank_acc_1')->nullable();
                $table->string('bank_ifsc_1')->nullable();
                $table->string('bank_branch_1')->nullable();
                $table->string('bank_qr_1')->nullable();
                $table->string('bank_name_2')->nullable();
                $table->string('bank_acc_2')->nullable();
                $table->string('bank_ifsc_2')->nullable();
                $table->string('bank_branch_2')->nullable();
                $table->string('bank_qr_2')->nullable();
                $table->string('bank_name_3')->nullable();
                $table->string('bank_acc_3')->nullable();
                $table->string('bank_ifsc_3')->nullable();
                $table->string('bank_branch_3')->nullable();
                $table->string('bank_qr_3')->nullable();
                $table->string('upi_id_1')->nullable();
                $table->string('upi_id_2')->nullable();
                $table->string('upi_id_3')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
