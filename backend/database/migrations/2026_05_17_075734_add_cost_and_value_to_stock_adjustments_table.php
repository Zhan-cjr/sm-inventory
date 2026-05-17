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
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->decimal('total_value', 15, 2)->default(0)->after('status');
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('new_quantity');
            $table->decimal('total_cost', 15, 2)->default(0)->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_cost']);
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('total_value');
        });
    }
};
