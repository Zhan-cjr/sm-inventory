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
        Schema::create('product_assemblies', function (Blueprint $table) {
            $table->id();
            $table->uuid('parent_product_id');
            $table->uuid('child_product_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();

            $table->foreign('parent_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('child_product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_assemblies');
    }
};
