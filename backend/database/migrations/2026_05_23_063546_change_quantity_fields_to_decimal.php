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
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity_on_hand', 12, 2)->default(0)->change();
            $table->decimal('quantity_reserved', 12, 2)->default(0)->change();
            $table->decimal('min_qty', 12, 2)->default(3)->change();
            $table->decimal('max_qty', 12, 2)->default(15)->change();
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
        });

        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->decimal('quantity_change', 12, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->integer('quantity_on_hand')->default(0)->change();
            $table->integer('quantity_reserved')->default(0)->change();
            $table->integer('min_qty')->default(10)->change();
            $table->integer('max_qty')->default(500)->change();
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->integer('quantity_change')->change();
        });
    }
};
