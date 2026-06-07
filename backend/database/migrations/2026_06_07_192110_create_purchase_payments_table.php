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
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->foreignUuid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('payment_method'); // cash, transfer, giro, dll
            $table->string('reference_number')->nullable(); // no giro / ref transfer
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('COMPLETED'); // DRAFT, COMPLETED, CANCELLED
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
