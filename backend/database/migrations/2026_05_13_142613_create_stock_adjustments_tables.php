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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('adjustment_number')->unique();
            $table->date('adjustment_date');
            $table->uuid('branch_id'); // Optional if it's per branch
            $table->string('notes')->nullable();
            $table->uuid('recorded_by');
            $table->string('status')->default('COMPLETED');
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->uuid('product_id');
            $table->integer('previous_quantity');
            $table->integer('adjustment_quantity');
            $table->integer('new_quantity');
            $table->string('reason_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
