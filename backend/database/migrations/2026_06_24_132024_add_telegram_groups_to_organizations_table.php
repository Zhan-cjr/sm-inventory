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
            $table->string('telegram_group_po_approval')->nullable()->after('po_approval_max_qty_enabled');
            $table->string('telegram_group_stock_correction')->nullable()->after('telegram_group_po_approval');
            $table->string('telegram_group_warehouse_check')->nullable()->after('telegram_group_stock_correction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_group_po_approval',
                'telegram_group_stock_correction',
                'telegram_group_warehouse_check'
            ]);
        });
    }
};
