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
        Schema::table('member_tiers', function (Blueprint $table) {
            $table->string('badge')->nullable()->after('name');
            $table->string('color_gradient')->nullable()->after('color_hex');
            $table->json('perks')->nullable()->after('color_gradient');
            $table->string('min_spend_text')->nullable()->after('min_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_tiers', function (Blueprint $table) {
            $table->dropColumn(['badge', 'color_gradient', 'perks', 'min_spend_text']);
        });
    }
};
