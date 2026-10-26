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
        // 1. Tambah kolom quantity_damaged pada tabel stocks jika belum ada
        if (Schema::hasTable('stocks') && !Schema::hasColumn('stocks', 'quantity_damaged')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->decimal('quantity_damaged', 12, 2)->default(0)->after('quantity_reserved');
            });
        }

        // 2. Buat tabel audit trail pergerakan stok karantina BS
        if (!Schema::hasTable('stock_quarantine_movements')) {
            Schema::create('stock_quarantine_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id')->nullable();
                $table->uuid('branch_id');
                $table->uuid('product_id');
                $table->string('movement_type', 50); // MOVE_TO_BS, RESTORE_TO_SALABLE, PURCHASE_RETURN, WRITE_OFF_DISPOSAL
                $table->decimal('quantity', 12, 2);
                $table->string('reason')->nullable();
                $table->string('reference_doc_type', 100)->nullable();
                $table->string('reference_doc_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'product_id'], 'idx_sqm_branch_product');
                $table->index('movement_type', 'idx_sqm_movement_type');
                $table->index('created_at', 'idx_sqm_created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_quarantine_movements');

        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'quantity_damaged')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('quantity_damaged');
            });
        }
    }
};
