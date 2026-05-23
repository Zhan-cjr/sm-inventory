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
            $table->decimal('margin_gol_1', 8, 2)->default(0)->after('selling_price');
            $table->decimal('harga_jual_1', 12, 2)->default(0)->after('margin_gol_1');
            $table->integer('qty_min_gol_1')->default(1)->after('harga_jual_1');

            $table->decimal('margin_gol_2', 8, 2)->nullable()->after('qty_min_gol_1');
            $table->decimal('harga_jual_2', 12, 2)->nullable()->after('margin_gol_2');
            $table->integer('qty_min_gol_2')->nullable()->after('harga_jual_2');

            $table->decimal('margin_gol_3', 8, 2)->nullable()->after('qty_min_gol_2');
            $table->decimal('harga_jual_3', 12, 2)->nullable()->after('margin_gol_3');
            $table->integer('qty_min_gol_3')->nullable()->after('harga_jual_3');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('cost_price_tax', 12, 2)->default(0)->after('cost_price');
            
            $table->decimal('margin_gol_1', 8, 2)->default(0)->after('selling_price');
            $table->decimal('harga_jual_1', 12, 2)->default(0)->after('margin_gol_1');
            $table->integer('qty_min_gol_1')->default(1)->after('harga_jual_1');

            $table->decimal('margin_gol_2', 8, 2)->nullable()->after('qty_min_gol_1');
            $table->decimal('harga_jual_2', 12, 2)->nullable()->after('margin_gol_2');
            $table->integer('qty_min_gol_2')->nullable()->after('harga_jual_2');

            $table->decimal('margin_gol_3', 8, 2)->nullable()->after('qty_min_gol_2');
            $table->decimal('harga_jual_3', 12, 2)->nullable()->after('margin_gol_3');
            $table->integer('qty_min_gol_3')->nullable()->after('harga_jual_3');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'margin_gol_1', 'harga_jual_1', 'qty_min_gol_1',
                'margin_gol_2', 'harga_jual_2', 'qty_min_gol_2',
                'margin_gol_3', 'harga_jual_3', 'qty_min_gol_3'
            ]);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'cost_price_tax',
                'margin_gol_1', 'harga_jual_1', 'qty_min_gol_1',
                'margin_gol_2', 'harga_jual_2', 'qty_min_gol_2',
                'margin_gol_3', 'harga_jual_3', 'qty_min_gol_3'
            ]);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable(false)->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable(false)->change();
        });
    }
};
