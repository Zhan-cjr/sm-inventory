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
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shift_id')->index();
            $table->foreignId('user_id')->constrained();
            $table->uuid('terminal_id')->index();
            $table->enum('type', ['CASH_IN', 'CASH_OUT']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->timestamps();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('total_cash_in', 15, 2)->default(0)->after('starting_cash');
            $table->decimal('total_cash_out', 15, 2)->default(0)->after('total_cash_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['total_cash_in', 'total_cash_out']);
        });
        Schema::dropIfExists('cash_movements');
    }
};
