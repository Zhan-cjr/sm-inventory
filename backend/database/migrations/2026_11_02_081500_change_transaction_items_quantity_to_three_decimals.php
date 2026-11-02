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
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('quantity', 15, 3)->change();
        });

        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->decimal('quantity_change', 15, 3)->change();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity_on_hand', 15, 3)->default(0)->change();
            $table->decimal('quantity_reserved', 15, 3)->default(0)->change();
            $table->decimal('quantity_damaged', 15, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity_on_hand', 12, 2)->default(0)->change();
            $table->decimal('quantity_reserved', 12, 2)->default(0)->change();
            $table->decimal('quantity_damaged', 12, 2)->default(0)->change();
        });

        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->decimal('quantity_change', 12, 2)->change();
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
        });
    }
};
