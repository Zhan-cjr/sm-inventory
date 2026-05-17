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
            $table->integer('lead_time')->default(3)->after('max_qty');
            $table->integer('safety_stock')->default(0)->after('lead_time');
            $table->integer('desired_inventory_days')->default(14)->after('safety_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['lead_time', 'safety_stock', 'desired_inventory_days']);
        });
    }
};
