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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->uuid('expense_account_id');
            $table->uuid('payment_account_id');
            $table->string('reference_number')->unique();
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->foreign('expense_account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('payment_account_id')->references('id')->on('accounts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
