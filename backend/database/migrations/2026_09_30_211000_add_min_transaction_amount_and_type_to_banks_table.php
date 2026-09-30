<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->string('type')->default('EDC')->after('code'); // EDC, QRIS, TRANSFER
            $table->decimal('min_transaction_amount', 14, 2)->default(50000.00)->after('type');
        });

        // Set existing records to default EDC min 50000
        DB::table('banks')->update([
            'type' => 'EDC',
            'min_transaction_amount' => 50000.00,
        ]);

        // Add QRIS bank if not already present
        $qrisExists = DB::table('banks')->where('code', 'QRIS')->exists();
        if (!$qrisExists) {
            DB::table('banks')->insert([
                'id'                     => (string) Str::uuid(),
                'name'                   => 'QRIS',
                'code'                   => 'QRIS',
                'type'                   => 'QRIS',
                'min_transaction_amount' => 20000.00,
                'is_active'              => true,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn(['type', 'min_transaction_amount']);
        });
    }
};
