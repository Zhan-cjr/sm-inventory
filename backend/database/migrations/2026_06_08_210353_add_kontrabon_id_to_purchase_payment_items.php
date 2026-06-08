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
        Schema::table('purchase_payment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_payment_items', 'kontrabon_id')) {
                $table->uuid('kontrabon_id')->nullable()->after('purchase_payment_id');
            }
        });
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE purchase_payment_items MODIFY goods_receipt_id CHAR(36) NULL DEFAULT NULL;');
        
        Schema::table('purchase_payment_items', function (Blueprint $table) {
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_payment_items', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_id']);
        });
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE purchase_payment_items MODIFY goods_receipt_id CHAR(36) NOT NULL;');
        
        Schema::table('purchase_payment_items', function (Blueprint $table) {
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->dropColumn('kontrabon_id');
        });
    }
};
