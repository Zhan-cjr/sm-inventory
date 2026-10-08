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
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'discount_1_type')) {
                $table->string('discount_1_type', 20)->default('percent')->after('discount_1');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'discount_2_type')) {
                $table->string('discount_2_type', 20)->default('percent')->after('discount_2');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'discount_3_type')) {
                $table->string('discount_3_type', 20)->default('percent')->after('discount_3');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('goods_receipt_items', 'discount_1_type')) $cols[] = 'discount_1_type';
            if (Schema::hasColumn('goods_receipt_items', 'discount_2_type')) $cols[] = 'discount_2_type';
            if (Schema::hasColumn('goods_receipt_items', 'discount_3_type')) $cols[] = 'discount_3_type';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
