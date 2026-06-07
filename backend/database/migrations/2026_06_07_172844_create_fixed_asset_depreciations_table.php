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
        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fixed_asset_id')->constrained()->cascadeOnDelete();
            
            $table->string('period', 7)->comment('Format: YYYY-MM'); // e.g., 2026-06
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_amount', 15, 2);
            $table->decimal('book_value', 15, 2);
            
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['fixed_asset_id', 'period'], 'unique_asset_period_depreciation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_depreciations');
    }
};
