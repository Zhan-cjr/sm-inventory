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
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreignUuid('goods_receipt_id')->nullable()->after('product_id')->constrained('goods_receipts')->nullOnDelete();
            $table->foreignUuid('stock_batch_id')->nullable()->after('goods_receipt_id')->constrained('stock_batches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_id']);
            $table->dropForeign(['stock_batch_id']);
            $table->dropColumn(['goods_receipt_id', 'stock_batch_id']);
        });
    }
};
