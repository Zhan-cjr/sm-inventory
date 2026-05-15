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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('selling_price');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('include_tax')->default(true)->after('total_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('include_tax');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->boolean('include_tax')->default(true)->after('total_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('include_tax');
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->decimal('discount_1', 15, 2)->default(0)->after('unit_price');
            $table->decimal('discount_2', 15, 2)->default(0)->after('discount_1');
            $table->decimal('discount_3', 15, 2)->default(0)->after('discount_2');
            $table->decimal('subtotal', 15, 2)->default(0)->after('discount_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_taxable');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['include_tax', 'tax_amount']);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['include_tax', 'tax_amount']);
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['discount_1', 'discount_2', 'discount_3', 'subtotal']);
        });
    }
};
