<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProductStandardizationService;

class StandardizeProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:standardize
                            {--dry-run : Simulasi tanpa mengubah data di database}
                            {--rollback= : Path ke file backup JSON untuk mengembalikan kondisi semula}
                            {--plan= : Path kustom ke file product_standardization_plan.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Standarisasi penamaan produk nasional, satuan RENCENG (RCG), konsolidasi kategori/subkategori/supplier, serta auto-merge SKU duplikat beda barcode beserta QOH & FIFO batch.';

    public function handle(ProductStandardizationService $service): int
    {
        if ($rollbackFile = $this->option('rollback')) {
            return $this->handleRollback($rollbackFile, $service);
        }

        $isDryRun = (bool) $this->option('dry-run');
        $planPath = $this->option('plan') ?: storage_path('app/product_standardization_plan.json');
        if (!file_exists($planPath)) {
            $altPath = base_path('../DATA IMPORT/product_standardization_plan.json');
            if (file_exists($altPath)) {
                $planPath = $altPath;
            } else {
                $this->error("File rencana standarisasi tidak ditemukan di: {$planPath}");
                return self::FAILURE;
            }
        }

        $plan = json_decode(file_get_contents($planPath), true);
        if (!$plan || !isset($plan['updates'], $plan['merges'])) {
            $this->error("Format file JSON rencana standarisasi tidak valid.");
            return self::FAILURE;
        }

        $updates = $plan['updates'];
        $merges = $plan['merges'];

        $this->info("==================================================================");
        $this->info("  STANDARISASI PRODUK & KONSOLIDASI SKU DUPLIKAT (SM-INVENTORY)   ");
        $this->info("==================================================================");
        $this->line("Total Rencana Update Atribut Produk : " . count($updates) . " SKU");
        $this->line("Total Rencana Merge SKU Duplikat    : " . count($merges) . " SKU");

        if ($isDryRun) {
            $this->warn("\n[MODE SIMULASI / DRY-RUN AKTIF] Tidak ada perubahan yang akan disimpan ke database.");
        }

        $orgId = Organization::value('id');
        if (!$orgId) {
            $this->error("Organization tidak ditemukan di database.");
            return self::FAILURE;
        }

        $recordedBy = (string) (User::value('id') ?: '00000000-0000-0000-0000-000000000000');

        $backupData = [
            'created_at' => now()->toIso8601String(),
            'products' => [],
            'stocks' => [],
            'stock_batches' => [],
            'created_inventory_log_ids' => [],
        ];

        $stats = [
            'updated_products' => 0,
            'missing_products' => 0,
            'merged_skus' => 0,
            'merged_stock_branches' => 0,
            'merged_qoh_total' => 0,
            'merged_batches' => 0,
        ];

        DB::beginTransaction();
        try {
            $this->info("\n[1/2] Memproses standarisasi nama, satuan RCG, kategori, sub-kategori, dan supplier...");
            $service->executeUpdates($updates, $orgId, $isDryRun, $backupData, $stats);

            $this->info("[2/2] Memproses konsolidasi SKU duplikat beda barcode, Multi-Barcode & Auto-Merge QOH...");
            $service->executeMerges($merges, $recordedBy, $isDryRun, $backupData, $stats);

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\n==================================================================");
                $this->info("  HASIL SIMULASI (DRY-RUN) BERHASIL — 0 ERROR                     ");
                $this->info("==================================================================");
            } else {
                $backupDir = storage_path('app/backups');
                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0755, true);
                }
                $backupFilename = $backupDir . '/product_standardization_backup_' . date('Ymd_His') . '.json';
                file_put_contents($backupFilename, json_encode($backupData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                DB::commit();
                $service->refreshCachesAndSearchIndex(array_keys($backupData['products']), $this);

                $this->info("\n==================================================================");
                $this->info("  EKSEKUSI BERHASIL & TERSIMPAN KE DATABASE                       ");
                $this->info("==================================================================");
                $this->line("File Backup Rollback : {$backupFilename}");
                $this->line("Untuk rollback kapan saja, jalankan:");
                $this->line("  php artisan products:standardize --rollback=\"{$backupFilename}\"");
            }

            $this->table(
                ['Metrik Eksekusi', 'Jumlah'],
                [
                    ['Produk Diperbarui (Nama / RCG / Kategori / Sub-Kategori / Supplier)', number_format($stats['updated_products'])],
                    ['SKU Duplikat Dinonaktifkan & Barcode Masuk Multi-Barcode Utama', number_format($stats['merged_skus'])],
                    ['Cabang Stok yang QOH-nya Digabung Otomatis', number_format($stats['merged_stock_branches'])],
                    ['Total Kuantitas Fisik (QOH) yang Digabung ke SKU Utama', number_format($stats['merged_qoh_total']) . ' pcs'],
                    ['Batch FIFO Aktif yang Dipindahkan ke SKU Utama', number_format($stats['merged_batches'])],
                    ['SKU di Excel yang Tidak Ditemukan di DB', number_format($stats['missing_products'])],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan, seluruh perubahan dibatalkan (Rolled Back): " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    protected function handleRollback(string $rollbackFile, ProductStandardizationService $service): int
    {
        if (!file_exists($rollbackFile)) {
            $this->error("File backup tidak ditemukan: {$rollbackFile}");
            return self::FAILURE;
        }

        $backup = json_decode(file_get_contents($rollbackFile), true);
        if (!$backup || !isset($backup['products'])) {
            $this->error("Format file backup tidak valid.");
            return self::FAILURE;
        }

        $this->warn("Memulai proses ROLLBACK dari snapshot: {$rollbackFile} ...");

        DB::beginTransaction();
        try {
            $service->rollback($backup);
            DB::commit();
            $service->refreshCachesAndSearchIndex(array_keys($backup['products']), $this);

            $this->info("ROLLBACK SELESAI! Seluruh data produk, stok, batch FIFO, dan kartu stok telah dikembalikan 100% ke kondisi semula.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal melakukan rollback: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
