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
        Schema::create('ecommerce_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->comment('Referensi ke tabel customers');
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('SYSTEM')->comment('ORDER, PROMO, SYSTEM');
            $table->boolean('is_read')->default(false);
            $table->uuid('reference_id')->nullable()->comment('ID Pesanan atau referensi lainnya');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_notifications');
    }
};
