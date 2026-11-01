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
        // 1. Tambah kolom last_so_number pada tabel stocks jika belum ada
        if (Schema::hasTable('stocks') && !Schema::hasColumn('stocks', 'last_so_number')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->string('last_so_number', 50)->nullable()->after('last_count_date');
            });
        }

        // 2. Buat tabel audit karantina BS (header)
        if (!Schema::hasTable('stock_quarantine_audits')) {
            Schema::create('stock_quarantine_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id')->nullable();
                $table->uuid('branch_id');
                $table->string('audit_number', 50)->unique();
                $table->date('audit_date');
                $table->enum('status', ['DRAFT', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
                $table->decimal('total_system_damaged', 12, 2)->default(0);
                $table->decimal('total_actual_damaged', 12, 2)->default(0);
                $table->decimal('total_discrepancy_qty', 12, 2)->default(0);
                $table->decimal('total_discrepancy_cost', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('audited_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'audit_date'], 'idx_sqa_branch_date');
                $table->index('status', 'idx_sqa_status');
            });
        }

        // 3. Buat tabel rincian audit karantina BS (items)
        if (!Schema::hasTable('stock_quarantine_audit_items')) {
            Schema::create('stock_quarantine_audit_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('audit_id');
                $table->uuid('product_id');
                $table->decimal('system_damaged_qty', 12, 2)->default(0);
                $table->decimal('actual_damaged_qty', 12, 2)->default(0);
                $table->decimal('discrepancy_qty', 12, 2)->default(0); // actual - system
                $table->decimal('cost_price', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0); // discrepancy * cost_price
                $table->string('action_taken', 50)->default('NONE'); // NONE, WRITE_OFF_DISPOSAL, RESTORE_TO_SALABLE, ADJUST_INCREASE
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['audit_id', 'product_id'], 'idx_sqai_audit_product');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_quarantine_audit_items');
        Schema::dropIfExists('stock_quarantine_audits');

        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'last_so_number')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('last_so_number');
            });
        }
    }
};

