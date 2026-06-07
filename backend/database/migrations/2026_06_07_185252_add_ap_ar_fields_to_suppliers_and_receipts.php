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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->integer('default_due_days')->default(0)->after('is_active')->comment('0 means Cash, >0 means Credit');
            $table->string('payment_method')->nullable()->after('default_due_days')->comment('cash, transfer, giro, dll');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('receipt_date');
            $table->string('payment_status')->default('unpaid')->after('status')->comment('unpaid, partial, paid');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'payment_status', 'paid_amount']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['default_due_days', 'payment_method']);
        });
    }
};
