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
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->string('customer_wa_phone')->nullable()->after('customer_name')->comment('Optional WA phone number for token/PDAM notification');
            $table->string('refund_status')->default('NOT_REFUNDED')->after('message')->comment('NOT_REFUNDED, REFUNDED');
            $table->string('refund_method')->nullable()->after('refund_status')->comment('CASH, TRANSFER, EWALLET');
            $table->decimal('refund_amount', 15, 2)->default(0)->after('refund_method');
            $table->foreignId('refunded_by')->nullable()->after('refund_amount')->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable()->after('refunded_by');
            $table->text('refund_notes')->nullable()->after('refunded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn([
                'customer_wa_phone',
                'refund_status',
                'refund_method',
                'refund_amount',
                'refunded_by',
                'refunded_at',
                'refund_notes'
            ]);
        });
    }
};
