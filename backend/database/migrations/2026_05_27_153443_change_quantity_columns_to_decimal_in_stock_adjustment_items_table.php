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
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->decimal('previous_quantity', 15, 3)->change();
            $table->decimal('adjustment_quantity', 15, 3)->change();
            $table->decimal('new_quantity', 15, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->integer('previous_quantity')->change();
            $table->integer('adjustment_quantity')->change();
            $table->integer('new_quantity')->change();
        });
    }
};
