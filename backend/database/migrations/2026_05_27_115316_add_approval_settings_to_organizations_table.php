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
        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('po_approval_limit', 15, 2)->nullable();
            $table->boolean('po_approval_max_qty_enabled')->default(false);
            $table->decimal('stock_adjustment_approval_amount_limit', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'po_approval_limit',
                'po_approval_max_qty_enabled',
                'stock_adjustment_approval_amount_limit'
            ]);
        });
    }
};
