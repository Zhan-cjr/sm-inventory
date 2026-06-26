<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->string('courier_name')->nullable();
            $table->string('courier_service')->nullable();
            $table->string('awb_number')->nullable();
            $table->string('biteship_order_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_cost', 'courier_name', 'courier_service', 'awb_number', 'biteship_order_id']);
        });
    }
};
