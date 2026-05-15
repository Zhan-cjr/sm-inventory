<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('faktur')->nullable()->after('po_date');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('discount_1', 12, 2)->default(0)->after('unit_cost');
            $table->decimal('discount_2', 12, 2)->default(0)->after('discount_1');
            $table->decimal('discount_3', 12, 2)->default(0)->after('discount_2');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_1', 'discount_2', 'discount_3']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('faktur');
        });
    }
};
