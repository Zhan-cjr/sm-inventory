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
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->decimal('quantity_before', 15, 3)->change();
            $table->decimal('quantity_after', 15, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->integer('quantity_before')->change();
            $table->integer('quantity_after')->change();
        });
    }
};
