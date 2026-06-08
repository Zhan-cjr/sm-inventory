<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_deductions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->uuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            $table->string('deduction_type'); // PROMO_RAFAKSI, LISTING_FEE, SEWA_DISPLAY, PURCHASE_RETURN, OTHER
            $table->uuid('reference_id')->nullable(); // ID Promo atau ID Retur
            
            $table->decimal('amount', 15, 2);
            $table->decimal('claimed_amount', 15, 2)->default(0);
            
            $table->string('status')->default('OPEN'); // OPEN, PARTIAL, COMPLETED
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_deductions');
    }
};
