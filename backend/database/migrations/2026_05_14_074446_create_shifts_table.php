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
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->uuid('branch_id');
            $table->uuid('terminal_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->decimal('starting_cash', 15, 2);
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->decimal('total_card_sales', 15, 2)->default(0);
            $table->decimal('actual_cash', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->default(0);
            $table->string('status')->default('OPEN');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('terminal_id')->references('id')->on('terminals');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
