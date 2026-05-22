<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('member_tier')->default('BRONZE')->change();
        });

        // Convert existing 'REGULAR' tiers to 'BRONZE'
        DB::table('customers')
            ->where('member_tier', 'REGULAR')
            ->update(['member_tier' => 'BRONZE']);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('member_tier')->default('REGULAR')->change();
        });

        DB::table('customers')
            ->where('member_tier', 'BRONZE')
            ->update(['member_tier' => 'REGULAR']);
    }
};
