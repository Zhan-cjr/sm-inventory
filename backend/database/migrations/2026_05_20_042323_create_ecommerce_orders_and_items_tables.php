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
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('delivery_method')->default('PICKUP'); // PICKUP / DELIVERY
            $table->text('delivery_address')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, PROCESSING, COMPLETED, CANCELLED
            $table->decimal('total_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ecommerce_order_id')->constrained('ecommerce_orders')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_items');
        Schema::dropIfExists('ecommerce_orders');
    }
};
