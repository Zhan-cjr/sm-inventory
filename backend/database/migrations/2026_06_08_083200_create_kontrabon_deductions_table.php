<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrabon_deductions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kontrabon_id')->constrained('kontrabons')->cascadeOnDelete();
            $table->uuid('supplier_deduction_id')->constrained('supplier_deductions')->cascadeOnDelete();
            $table->decimal('amount_applied', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrabon_deductions');
    }
};
