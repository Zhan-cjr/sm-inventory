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
            $table->uuid('adjustment_reason_id')->nullable()->after('adjustment_date');
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->dropColumn('reason_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->string('reason_code')->after('new_quantity')->nullable();
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('adjustment_reason_id');
        });
    }
};
