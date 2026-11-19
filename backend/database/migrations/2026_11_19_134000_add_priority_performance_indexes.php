<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar index komposit prioritas untuk skala multi-cabang.
     * 100% aditif (hanya ADD INDEX, tidak mengubah data/kolom) dan idempoten.
     *
     * @var array<int, array{0: string, 1: array<int, string>, 2: string}>
     */
    protected array $indexes = [
        // 1. transactions — mencegah full-table lock saat POS offline sync & mempercepat dashboard/shift
        ['transactions', ['local_transaction_id'], 'idx_tx_local_tx_id'],
        ['transactions', ['branch_id', 'is_voided', 'transaction_date'], 'idx_tx_branch_void_date'],
        ['transactions', ['branch_id', 'terminal_id', 'created_at'], 'idx_tx_branch_term_created'],
        ['transactions', ['shift_id', 'is_voided'], 'idx_tx_shift_void'],

        // 2. transaction_items — mempercepat reprint struk, retur, konsinyasi, & laporan
        ['transaction_items', ['original_transaction_id'], 'idx_ti_orig_tx_id'],
        ['transaction_items', ['kontrabon_id'], 'idx_ti_kontrabon_id'],
        ['transaction_items', ['promotion_id'], 'idx_ti_promotion_id'],
        ['transaction_items', ['transaction_id', 'product_id'], 'idx_ti_tx_product'],

        // 3. stock_batches — mempercepat FIFO checkout (LOCK FOR UPDATE) & lookup dokumen GR/Retur
        ['stock_batches', ['product_id', 'branch_id', 'remaining_quantity', 'entry_date'], 'idx_sb_fifo_lookup'],
        ['stock_batches', ['reference_doc_type', 'reference_doc_id'], 'idx_sb_ref_doc'],

        // 4. inventory_logs — mempercepat Kartu Stok & rekonsiliasi Stok Opname
        ['inventory_logs', ['branch_id', 'product_id', 'created_at'], 'idx_invlog_branch_prod_created'],
        ['inventory_logs', ['branch_id', 'created_at'], 'idx_invlog_branch_created'],
        ['inventory_logs', ['reference_doc_type', 'reference_doc_id'], 'idx_invlog_ref_doc'],

        // 5. journal_entries & journal_entry_lines — mempercepat Neraca, Laba Rugi, & Buku Besar
        ['journal_entries', ['branch_id', 'status', 'entry_date'], 'idx_je_branch_status_date'],
        ['journal_entries', ['status', 'entry_date'], 'idx_je_status_date'],
        ['journal_entry_lines', ['account_id', 'journal_entry_id', 'debit', 'credit'], 'idx_jel_acc_je_amounts'],

        // 6. purchase_orders & goods_receipts — mempercepat operasional pembelian & AP/Kontrabon
        ['purchase_orders', ['branch_id', 'status', 'created_at'], 'idx_po_branch_status_created'],
        ['purchase_orders', ['supplier_id', 'status'], 'idx_po_supplier_status'],
        ['goods_receipts', ['branch_id', 'status', 'receipt_date'], 'idx_gr_branch_status_date'],
        ['goods_receipts', ['supplier_id', 'payment_status', 'due_date'], 'idx_gr_supp_pay_due'],

        // 7. stock_opname_items & stock_adjustments — mempercepat mobile opname & koreksi stok
        ['stock_opname_items', ['session_id', 'status'], 'idx_soi_session_status'],
        ['stock_opname_items', ['session_id', 'product_id'], 'idx_soi_session_product'],
        ['stock_adjustments', ['branch_id', 'adjustment_date'], 'idx_sa_branch_date'],
        ['stock_adjustments', ['branch_id', 'status'], 'idx_sa_branch_status'],
        ['stock_adjustment_items', ['product_id'], 'idx_sai_product_id'],

        // 8. activity_log & notifications — mempercepat log aktivitas & notifikasi Filament
        ['activity_log', ['created_at'], 'idx_actlog_created_at'],
        ['activity_log', ['subject_type', 'subject_id', 'created_at'], 'idx_actlog_subject_created'],
        ['notifications', ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'idx_notif_unread_created'],
    ];

    protected function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT COUNT(1) AS cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $indexName]
        );

        return ((int) ($result->cnt ?? 0)) > 0;
    }

    public function up(): void
    {
        foreach ($this->indexes as [$tableName, $columns, $indexName]) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumns($tableName, $columns)) {
                continue;
            }
            if ($this->indexExists($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes) as [$tableName, $columns, $indexName]) {
            if (!Schema::hasTable($tableName) || !$this->indexExists($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};

