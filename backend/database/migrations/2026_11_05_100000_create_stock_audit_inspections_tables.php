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
        Schema::create('stock_audit_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('audit_number', 50)->unique()->comment('Nomor Sidak: SDK-YYYYMMDD-XXXX');
            $table->foreignUuid('branch_id')->constrained('branches');
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->unsignedBigInteger('auditor_id');
            $table->foreign('auditor_id')->references('id')->on('users');
            $table->date('inspection_date');
            $table->enum('inspection_mode', ['FREE_SCAN', 'RANDOM_LOTTERY', 'RISK_BASED'])->default('FREE_SCAN');
            $table->enum('status', [
                'DRAFT',                 // Sedang berlangsung / proses scan
                'INVESTIGATION_PENDING', // Selesai sidak, menunggu klarifikasi toko (stok belum diubah)
                'ADJUSTED',              // Stok sudah dikoreksi ke sistem
                'CLOSED'                 // Ditutup tanpa koreksi stok (misal barang ketemu)
            ])->default('DRAFT');
            $table->integer('total_items_checked')->default(0);
            $table->integer('total_items_discrepancy')->default(0);
            $table->decimal('total_loss_nominal', 15, 2)->default(0);
            $table->decimal('total_gain_nominal', 15, 2)->default(0);
            $table->string('acknowledged_by', 100)->nullable()->comment('Nama Kepala Toko/Saksi');
            $table->text('notes')->nullable();
            $table->foreignUuid('adjustment_id')->nullable()->constrained('stock_adjustments')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_audit_inspection_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inspection_id')->constrained('stock_audit_inspections')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('rack_id')->nullable()->constrained('stock_opname_racks')->nullOnDelete();
            $table->decimal('system_quantity', 12, 2)->default(0);
            $table->decimal('delta_sales_quantity', 12, 2)->default(0)->comment('Mutasi penjualan/retur selama sidak berlangsung');
            $table->decimal('physical_quantity', 12, 2)->default(0);
            $table->decimal('discrepancy_quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('discrepancy_cost', 15, 2)->default(0);
            $table->enum('status', ['MATCH', 'MINUS', 'PLUS'])->default('MATCH');
            $table->string('clarification_notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_audit_inspection_items');
        Schema::dropIfExists('stock_audit_inspections');
    }
};

