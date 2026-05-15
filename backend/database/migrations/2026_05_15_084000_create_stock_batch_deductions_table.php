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
        Schema::create('stock_batch_deductions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_batch_id');
            $table->uuid('transaction_item_id');
            $table->decimal('quantity', 18, 4);
            $table->timestamps();

            $table->foreign('stock_batch_id')->references('id')->on('stock_batches')->onDelete('cascade');
            $table->foreign('transaction_item_id')->references('id')->on('transaction_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batch_deductions');
    }
};
