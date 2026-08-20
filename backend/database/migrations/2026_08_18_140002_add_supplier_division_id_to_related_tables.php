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
        // purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'supplier_division_id')) {
                $table->foreignUuid('supplier_division_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('supplier_divisions')
                    ->nullOnDelete();
            }
        });

        // goods_receipts
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'supplier_division_id')) {
                $table->foreignUuid('supplier_division_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('supplier_divisions')
                    ->nullOnDelete();
            }
        });

        // purchase_returns
        Schema::table('purchase_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_returns', 'supplier_division_id')) {
                $table->foreignUuid('supplier_division_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('supplier_divisions')
                    ->nullOnDelete();
            }
        });

        // kontrabons
        Schema::table('kontrabons', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrabons', 'supplier_division_id')) {
                $table->foreignUuid('supplier_division_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('supplier_divisions')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontrabons', function (Blueprint $table) {
            if (Schema::hasColumn('kontrabons', 'supplier_division_id')) {
                $table->dropForeign(['supplier_division_id']);
                $table->dropColumn('supplier_division_id');
            }
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_returns', 'supplier_division_id')) {
                $table->dropForeign(['supplier_division_id']);
                $table->dropColumn('supplier_division_id');
            }
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'supplier_division_id')) {
                $table->dropForeign(['supplier_division_id']);
                $table->dropColumn('supplier_division_id');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'supplier_division_id')) {
                $table->dropForeign(['supplier_division_id']);
                $table->dropColumn('supplier_division_id');
            }
        });
    }
};
