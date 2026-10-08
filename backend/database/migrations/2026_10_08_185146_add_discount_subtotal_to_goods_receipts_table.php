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
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->decimal('discount_subtotal', 15, 2)->default(0)->after('total_amount');
            $table->string('discount_subtotal_type', 20)->default('nominal')->after('discount_subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['discount_subtotal', 'discount_subtotal_type']);
        });
    }
};
