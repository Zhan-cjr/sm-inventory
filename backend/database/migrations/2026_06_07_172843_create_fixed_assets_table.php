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
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0)->comment('Nilai sisa / residu');
            $table->integer('useful_life_years')->comment('Umur ekonomis dalam tahun');
            
            // Accounting Mappings
            $table->foreignUuid('asset_account_id')->constrained('accounts'); // e.g. Kendaraan
            $table->foreignUuid('accumulated_depreciation_account_id')->constrained('accounts'); // e.g. Akumulasi Penyusutan
            $table->foreignUuid('depreciation_expense_account_id')->constrained('accounts'); // e.g. Beban Penyusutan
            $table->foreignUuid('payment_account_id')->nullable()->constrained('accounts'); // Source of funds used to purchase (if null, assumed already recorded/opening balance)
            
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
