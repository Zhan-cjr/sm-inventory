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
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->default(0)->after('status');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->default(0)->after('quantity');
            $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'subtotal']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};
