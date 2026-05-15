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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('branch_id');
            $table->string('reference_doc_type')->nullable();
            $table->uuid('reference_doc_id')->nullable();
            $table->decimal('initial_quantity', 18, 4);
            $table->decimal('remaining_quantity', 18, 4);
            $table->decimal('cost_price', 18, 4)->nullable();
            $table->dateTime('entry_date');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index(['product_id', 'branch_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
