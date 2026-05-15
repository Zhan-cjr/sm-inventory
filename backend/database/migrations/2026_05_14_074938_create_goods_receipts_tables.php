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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id')->nullable();
            $table->uuid('supplier_id');
            $table->uuid('branch_id');
            $table->string('receipt_number')->unique();
            $table->dateTime('receipt_date');
            $table->string('received_by');
            $table->string('faktur_supplier')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('status')->default('RECEIVED');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('goods_receipt_id');
            $table->uuid('product_id');
            $table->decimal('quantity_ordered', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
