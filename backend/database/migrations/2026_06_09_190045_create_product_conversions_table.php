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
        Schema::create('product_conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('source_product_id'); // Beras Karung
            $table->uuid('target_product_id'); // Beras Curah
            $table->decimal('conversion_qty', 10, 2)->default(1); // 1 Karung = 15 Curah
            $table->boolean('auto_convert')->default(true);
            $table->timestamps();

            $table->foreign('source_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('target_product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_conversions');
    }
};
