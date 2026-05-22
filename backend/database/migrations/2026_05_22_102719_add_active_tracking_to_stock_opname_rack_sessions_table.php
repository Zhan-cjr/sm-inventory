<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_rack_sessions', function (Blueprint $table) {
            $table->timestamp('active_count_at')->nullable()->after('count1_at');
            $table->timestamp('active_check_at')->nullable()->after('count2_at');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_rack_sessions', function (Blueprint $table) {
            $table->dropColumn(['active_count_at', 'active_check_at']);
        });
    }
};
