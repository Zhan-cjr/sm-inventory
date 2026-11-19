<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\StockBatch;
use App\Models\InventoryLog;
use App\Models\Branch;

class ProductStandardizationService
{
    public function executeUpdates(array $updates, string $orgId, bool $isDryRun, array &$backupData, array &$stats): void
    {
        $categoryMap = [];
        foreach (Category::where('organization_id', $orgId)->get() as $cat) {
            $categoryMap[strtoupper(trim($cat->name))] = $cat->id;
        }
        $supplierMap = [];
        foreach (Supplier::where('organization_id', $orgId)->get() as $sup) {
            $supplierMap[strtoupper(trim($sup->name))] = $sup->id;
        }

        foreach ($updates as $item) {
            $product = DB::table('products')->where('id', $item['id'])->first()
                ?: (!empty($item['sku']) ? DB::table('products')->where('sku', $item['sku'])->first() : null);
            if (!$product) {
                $stats['missing_products']++;
                continue;
            }

            $targetCatName = strtoupper(trim($item['new_cat']));
            if (!isset($categoryMap[$targetCatName])) {
                if (!$isDryRun) {
                    $newCat = Category::create(['organization_id' => $orgId, 'name' => trim($item['new_cat']), 'is_active' => true]);
                    $categoryMap[$targetCatName] = $newCat->id;
                } else {
                    $categoryMap[$targetCatName] = $product->category_id;
                }
            }
            $newCategoryId = $categoryMap[$targetCatName];

            $targetSupName = strtoupper(trim($item['new_sup']));
            $newSupplierId = $product->supplier_id;
            if (!empty($targetSupName)) {
                if (!isset($supplierMap[$targetSupName]) && !$isDryRun) {
                    $newSup = Supplier::create(['organization_id' => $orgId, 'name' => trim($item['new_sup']), 'is_active' => true]);
                    $supplierMap[$targetSupName] = $newSup->id;
                }
                $newSupplierId = $supplierMap[$targetSupName] ?? $product->supplier_id;
            }

            $this->snapshotProduct($backupData, $product);
            if (!$isDryRun) {
                DB::table('products')->where('id', $product->id)->update([
                    'name' => $item['new_name'],
                    'unit_of_measure' => $item['new_uom'],
                    'category_id' => $newCategoryId,
                    'sub_category' => $item['new_subcat'],
                    'supplier_id' => $newSupplierId,
                    'updated_at' => now(),
                ]);
            }
            $stats['updated_products']++;
        }
    }

    public function executeMerges(array $merges, string $recordedBy, bool $isDryRun, array &$backupData, array &$stats): void
    {
        foreach ($merges as $m) {
            $master = DB::table('products')->where('id', $m['master_id'])->first()
                ?: DB::table('products')->where('sku', $m['master_sku'])->first();
            $dup = DB::table('products')->where('id', $m['dup_id'])->first()
                ?: DB::table('products')->where('sku', $m['dup_sku'])->first();

            if (!$master || !$dup || $master->id === $dup->id) {
                continue;
            }

            $this->snapshotProduct($backupData, $master);
            $this->snapshotProduct($backupData, $dup);
            $dupBarcode = trim((string) $dup->barcode);

            $this->mergeBranchStocks($master, $dup, $dupBarcode, $recordedBy, $isDryRun, $backupData, $stats);
            $this->mergeStockBatches($master, $dup, $isDryRun, $backupData, $stats);
            $this->deactivateDuplicateAndAttachBarcode($master, $dup, $dupBarcode, $isDryRun);
            $stats['merged_skus']++;
        }
    }

    protected function snapshotProduct(array &$backupData, object $product): void
    {
        if (!isset($backupData['products'][$product->id])) {
            $backupData['products'][$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'unit_of_measure' => $product->unit_of_measure,
                'category_id' => $product->category_id,
                'sub_category' => $product->sub_category,
                'supplier_id' => $product->supplier_id,
                'barcode' => $product->barcode,
                'is_active' => $product->is_active,
                'is_ecommerce_active' => $product->is_ecommerce_active ?? false,
                'metadata' => $product->metadata,
            ];
        }
    }

    protected function mergeBranchStocks(object $master, object $dup, string $dupBarcode, string $recordedBy, bool $isDryRun, array &$backupData, array &$stats): void
    {
        foreach (Stock::where('product_id', $dup->id)->get() as $dupStock) {
            $dupQoh = (float) $dupStock->quantity_on_hand;
            $dupDamaged = (float) ($dupStock->quantity_damaged ?? 0);
            if ($dupQoh <= 0 && $dupDamaged <= 0 && !$dupStock->is_active) {
                continue;
            }

            $backupData['stocks'][$dupStock->id] = [
                'id' => $dupStock->id, 'exists' => true,
                'quantity_on_hand' => $dupStock->quantity_on_hand,
                'quantity_damaged' => $dupStock->quantity_damaged,
                'is_active' => $dupStock->is_active,
            ];

            if ($dupQoh > 0 || $dupDamaged > 0) {
                $masterStock = Stock::where('product_id', $master->id)->where('branch_id', $dupStock->branch_id)->first();
                if ($masterStock && !isset($backupData['stocks'][$masterStock->id])) {
                    $backupData['stocks'][$masterStock->id] = [
                        'id' => $masterStock->id, 'exists' => true,
                        'quantity_on_hand' => $masterStock->quantity_on_hand,
                        'quantity_damaged' => $masterStock->quantity_damaged,
                        'is_active' => $masterStock->is_active,
                    ];
                }

                if (!$isDryRun) {
                    if (!$masterStock) {
                        $masterStock = new Stock([
                            'branch_id' => $dupStock->branch_id, 'product_id' => $master->id,
                            'supplier_id' => $dupStock->supplier_id, 'cost_price' => $dupStock->cost_price,
                            'cost_price_tax' => $dupStock->cost_price_tax, 'selling_price' => $dupStock->selling_price,
                            'quantity_on_hand' => 0, 'quantity_damaged' => 0, 'is_active' => true,
                        ]);
                        $masterStock->saveQuietly();
                        $backupData['stocks'][$masterStock->id] = ['id' => $masterStock->id, 'exists' => false];
                    }

                    $masterOldQoh = (float) $masterStock->quantity_on_hand;
                    $masterNewQoh = $masterOldQoh + $dupQoh;
                    DB::table('stocks')->where('id', $masterStock->id)->update([
                        'quantity_on_hand' => $masterNewQoh,
                        'quantity_damaged' => ((float) ($masterStock->quantity_damaged ?? 0)) + $dupDamaged,
                        'is_active' => true, 'updated_at' => now(),
                    ]);

                    if ($dupQoh != 0) {
                        $logIn = InventoryLog::create([
                            'branch_id' => $dupStock->branch_id, 'product_id' => $master->id, 'log_type' => 'ADJUSTMENT',
                            'quantity_before' => $masterOldQoh, 'quantity_change' => $dupQoh,
                            'quantity_after' => $masterNewQoh, 'balance' => $masterNewQoh,
                            'reason_code' => 'MERGE_DUPLICATE_SKU_IN', 'reference_doc_type' => 'PRODUCT_MERGE',
                            'reference_doc_id' => $dup->id, 'recorded_by' => $recordedBy,
                            'notes' => "Konsolidasi otomatis dari SKU duplikat {$dup->sku} (Barcode: {$dupBarcode})",
                        ]);
                        $logOut = InventoryLog::create([
                            'branch_id' => $dupStock->branch_id, 'product_id' => $dup->id, 'log_type' => 'ADJUSTMENT',
                            'quantity_before' => $dupQoh, 'quantity_change' => -$dupQoh,
                            'quantity_after' => 0, 'balance' => 0,
                            'reason_code' => 'MERGE_DUPLICATE_SKU_OUT', 'reference_doc_type' => 'PRODUCT_MERGE',
                            'reference_doc_id' => $master->id, 'recorded_by' => $recordedBy,
                            'notes' => "Konsolidasi otomatis ke SKU utama {$master->sku} (Barcode: {$master->barcode})",
                        ]);
                        $backupData['created_inventory_log_ids'][] = $logIn->id;
                        $backupData['created_inventory_log_ids'][] = $logOut->id;
                    }
                }
                $stats['merged_stock_branches']++;
                $stats['merged_qoh_total'] += $dupQoh;
            }

            if (!$isDryRun) {
                DB::table('stocks')->where('id', $dupStock->id)->update([
                    'quantity_on_hand' => 0, 'quantity_damaged' => 0, 'is_active' => false, 'updated_at' => now(),
                ]);
            }
        }
    }

    protected function mergeStockBatches(object $master, object $dup, bool $isDryRun, array &$backupData, array &$stats): void
    {
        foreach (StockBatch::where('product_id', $dup->id)->where('remaining_quantity', '>', 0)->get() as $batch) {
            $backupData['stock_batches'][$batch->id] = ['id' => $batch->id, 'product_id' => $batch->product_id];
            if (!$isDryRun) {
                DB::table('stock_batches')->where('id', $batch->id)->update(['product_id' => $master->id, 'updated_at' => now()]);
            }
            $stats['merged_batches']++;
        }
    }

    protected function deactivateDuplicateAndAttachBarcode(object $master, object $dup, string $dupBarcode, bool $isDryRun): void
    {
        $masterMeta = is_string($master->metadata) ? (json_decode($master->metadata, true) ?: []) : [];
        $addBarcodes = $masterMeta['additional_barcodes'] ?? [];
        if (!is_array($addBarcodes)) {
            $addBarcodes = array_filter(array_map('trim', explode(',', (string) $addBarcodes)));
        }
        if ($dupBarcode !== '' && $dupBarcode !== $master->barcode && !in_array($dupBarcode, $addBarcodes, true)) {
            $addBarcodes[] = $dupBarcode;
        }
        $masterMeta['additional_barcodes'] = array_values(array_unique($addBarcodes));

        $dupMeta = is_string($dup->metadata) ? (json_decode($dup->metadata, true) ?: []) : [];
        $dupMeta['merged_into_product_id'] = $master->id;
        $dupMeta['merged_into_sku'] = $master->sku;
        $dupMeta['previous_barcode'] = $dupBarcode;

        if (!$isDryRun) {
            DB::table('products')->where('id', $dup->id)->update([
                'barcode' => null, 'is_active' => false, 'is_ecommerce_active' => false,
                'metadata' => json_encode($dupMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
            DB::table('products')->where('id', $master->id)->update([
                'metadata' => json_encode($masterMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    public function rollback(array $backup): void
    {
        foreach ($backup['products'] as $prodId => $pData) {
            DB::table('products')->where('id', $prodId)->update([
                'name' => $pData['name'], 'unit_of_measure' => $pData['unit_of_measure'],
                'category_id' => $pData['category_id'], 'sub_category' => $pData['sub_category'],
                'supplier_id' => $pData['supplier_id'], 'barcode' => $pData['barcode'],
                'is_active' => $pData['is_active'], 'is_ecommerce_active' => $pData['is_ecommerce_active'] ?? false,
                'metadata' => $pData['metadata'], 'updated_at' => now(),
            ]);
        }
        foreach ($backup['stocks'] ?? [] as $stockId => $sData) {
            if (!($sData['exists'] ?? true)) {
                DB::table('stocks')->where('id', $stockId)->delete();
            } else {
                DB::table('stocks')->where('id', $stockId)->update([
                    'quantity_on_hand' => $sData['quantity_on_hand'], 'quantity_damaged' => $sData['quantity_damaged'],
                    'is_active' => $sData['is_active'], 'updated_at' => now(),
                ]);
            }
        }
        foreach ($backup['stock_batches'] ?? [] as $batchId => $bData) {
            DB::table('stock_batches')->where('id', $batchId)->update(['product_id' => $bData['product_id'], 'updated_at' => now()]);
        }
        if (!empty($backup['created_inventory_log_ids'])) {
            DB::table('inventory_logs')->whereIn('id', $backup['created_inventory_log_ids'])->delete();
        }
    }

    public function refreshCachesAndSearchIndex(array $productIds, ?Command $command = null): void
    {
        try {
            Cache::forget('ecommerce_products_all');
            foreach (Branch::pluck('id') as $branchId) {
                Cache::forget('ecommerce_products_' . $branchId);
                Cache::forget('pos_products_json_gz_branch_' . $branchId);
            }
            Cache::flush();
        } catch (\Throwable $e) {
            $command?->warn("Peringatan saat membersihkan cache: " . $e->getMessage());
        }
        if (!empty($productIds) && config('scout.driver') && config('scout.driver') !== 'null') {
            $command?->info("Menyinkronkan indeks pencarian (Meilisearch/Scout) untuk " . count($productIds) . " produk...");
            try {
                foreach (array_chunk($productIds, 500) as $chunk) {
                    Product::with(['category', 'stocks'])->whereIn('id', $chunk)->get()->searchable();
                }
            } catch (\Throwable $e) {
                $command?->warn("Peringatan sinkronisasi Scout/Meilisearch: " . $e->getMessage());
            }
        }
    }
}
