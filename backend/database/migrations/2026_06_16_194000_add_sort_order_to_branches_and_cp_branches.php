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
        Schema::table('branches', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        Schema::table('cp_branches', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('cp_branches', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
