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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_number')->unique();
            $table->uuid('from_branch_id');
            $table->uuid('to_branch_id');
            $table->enum('status', ['pending', 'in_transit', 'received', 'rejected'])->default('pending');
            $table->date('transfer_date');
            $table->date('received_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('from_branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('to_branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
