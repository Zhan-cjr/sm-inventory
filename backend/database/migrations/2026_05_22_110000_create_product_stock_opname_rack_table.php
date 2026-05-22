<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_opname_rack', function (Blueprint $table) {
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('rack_id')->constrained('stock_opname_racks')->cascadeOnDelete();
            $table->primary(['product_id', 'rack_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_opname_rack');
    }
};
