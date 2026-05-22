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
            $table->decimal('latitude', 10, 8)->nullable()->after('phone');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->unique()->nullable()->change();
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
