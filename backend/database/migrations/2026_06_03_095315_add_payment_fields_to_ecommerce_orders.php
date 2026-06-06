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
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('status');
            $table->string('payment_status')->default('UNPAID')->after('payment_method');
            $table->string('snap_token')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'snap_token']);
        });
    }
};
