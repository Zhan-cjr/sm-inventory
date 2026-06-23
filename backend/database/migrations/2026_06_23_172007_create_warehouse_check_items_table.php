<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_check_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_check_id');
            $table->uuid('product_id');
            $table->decimal('qty_po', 12, 2)->default(0);
            $table->decimal('qty_scanned', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('warehouse_check_id')->references('id')->on('warehouse_checks')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_check_items');
    }
};
