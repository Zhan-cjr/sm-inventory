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
            $table->string('telegram_group_daily_report')->nullable()->after('telegram_group_warehouse_check');
        });
        
        // Copy the group ID from po_approval if it exists, so the user doesn't have to fill it again
        DB::statement('UPDATE organizations SET telegram_group_daily_report = telegram_group_po_approval WHERE telegram_group_po_approval IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('telegram_group_daily_report');
        });
    }
};
