<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockAuditInspection;
use App\Models\StockAuditInspectionItem;
use App\Models\Branch;
use App\Models\StockOpnameSession;
use App\Models\StockOpnameRack;
use App\Models\StockOpnameRackSession;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAuditService
{
    /**
     * Buat Sesi Sidak Baru
     */
    public function createInspection(array $data, User $user): StockAuditInspection
    {
        $branchId = $data['branch_id'] ?? $user->branch_id;
        $orgId = $data['organization_id'] ?? $user->organization_id;
        $mode = $data['inspection_mode'] ?? 'FREE_SCAN';

        // Fallback jika user adalah Super Admin / akun pusat tanpa branch_id
        if (!$branchId) {
            $branchId = \App\Models\Branch::where('organization_id', $orgId)->value('id') 
                ?? \App\Models\Branch::first()?->id;
        }

        if (!$orgId && $branchId) {
            $orgId = \App\Models\Branch::where('id', $branchId)->value('organization_id');
        }

        $auditNumber = 'SDK-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        return DB::transaction(function () use ($data, $user, $branchId, $orgId, $mode, $auditNumber) {
            $inspection = StockAuditInspection::create([
                'audit_number'            => $auditNumber,
                'branch_id'               => $branchId,
                'organization_id'         => $orgId,
                'auditor_id'              => $user->id,
                'inspection_date'         => $data['inspection_date'] ?? now()->toDateString(),
                'inspection_mode'         => $mode,
                'status'                  => 'DRAFT',
                'notes'                   => $data['notes'] ?? null,
                'total_items_checked'     => 0,
                'total_items_discrepancy' => 0,
                'total_loss_nominal'      => 0,
                'total_gain_nominal'      => 0,
            ]);

            // Jika mode Undian Acak (RANDOM_LOTTERY)
            if ($mode === 'RANDOM_LOTTERY') {
                $targetCount = (int) ($data['target_count'] ?? 20);
                if ($targetCount <= 0) $targetCount = 20;

                $query = Stock::with(['product', 'racks'])
                    ->where('branch_id', $branchId)
                    ->whereHas('product', function ($q) {
                        $q->where('is_active', true)
                          ->where(function ($sub) {
                              $sub->whereNull('product_type')
                                  ->orWhere('product_type', '!=', 'digital');
                          });
                    });

                // Filter rak jika ada
                if (!empty($data['rack_ids']) && is_array($data['rack_ids'])) {
                    $query->whereHas('racks', function ($q) use ($data) {
                        $q->whereIn('stock_opname_racks.id', $data['rack_ids']);
                    });
                }

                // Filter kategori jika ada
                if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
                    $query->whereHas('product', function ($q) use ($data) {
                        $q->whereIn('category_id', $data['category_ids']);
                    });
                }

                // Filter khusus High-Risk (bernilai tinggi / HPP tertinggi)
                if (!empty($data['high_risk_only'])) {
                    $query->orderByDesc('cost_price_tax')->limit($targetCount * 3);
                }

                $stocks = $query->inRandomOrder()->limit($targetCount)->get();

                foreach ($stocks as $st) {
                    $prod = $st->product;
                    if (!$prod) continue;

                    $unitCost = (float) ($st->cost_price_tax > 0 ? $st->cost_price_tax : ($st->cost_price > 0 ? $st->cost_price : ($prod->cost_price_tax ?? $prod->cost_price ?? 0)));
                    $systemQty = (float) $st->salable_quantity;
                    $firstRack = $st->racks->first();

                    StockAuditInspectionItem::create([
                        'inspection_id'        => $inspection->id,
                        'product_id'           => $prod->id,
                        'rack_id'              => $firstRack?->id,
                        'system_quantity'      => $systemQty,
                        'delta_sales_quantity' => 0,
                        'physical_quantity'    => 0,
                        'discrepancy_quantity' => -$systemQty,
                        'unit_cost'            => $unitCost,
                        'discrepancy_cost'     => -($systemQty * $unitCost),
                        'status'               => $systemQty > 0 ? 'MINUS' : 'MATCH',
                    ]);
                }

                $inspection->recalculateSummary();
            }

            return $inspection;
        });
    }

    /**
     * Cari produk berdasarkan Barcode, SKU, atau Nama
     */
    public function searchProduct(string $queryStr, string $branchId)
    {
        $queryStr = trim($queryStr);
        if (empty($queryStr)) return collect();

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($queryStr) {
                $q->where('barcode', $queryStr)
                  ->orWhere('sku', $queryStr)
                  ->orWhereJsonContains('metadata->additional_barcodes', $queryStr)
                  ->orWhere('metadata->additional_barcodes', 'LIKE', '%' . $queryStr . '%')
                  ->orWhere('name', 'LIKE', '%' . $queryStr . '%');
            })
            ->where(function ($q) {
                $q->whereNull('product_type')
                  ->orWhere('product_type', '!=', 'digital');
            })
            ->limit(15)
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $stocks = Stock::where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->with('racks')
            ->get()
            ->keyBy('product_id');

        return $products->map(function ($p) use ($stocks, $branchId) {
            $st = $stocks->get($p->id);
            $isAssigned = $st !== null;
            $qoh = (float) ($st?->quantity_on_hand ?? 0);
            $salable = (float) ($st?->salable_quantity ?? $qoh);
            $cost = (float) ($st?->cost_price_tax > 0 ? $st->cost_price_tax : ($st?->cost_price > 0 ? $st->cost_price : ($p->cost_price_tax ?? $p->cost_price ?? 0)));
            $racks = $st?->racks?->pluck('rack_name')->toArray() ?? [];

            return [
                'id'                    => $p->id,
                'name'                  => $p->name,
                'sku'                   => $p->sku,
                'barcode'               => $p->barcode,
                'unit'                  => $p->unit ?? 'pcs',
                'cost_price'            => $cost,
                'stock'                 => $salable,
                'is_assigned_to_branch' => $isAssigned,
                'branch_id'             => $branchId,
                'racks'                 => !empty($racks) ? implode(', ', $racks) : '-',
                'rack_id'               => $st?->racks?->first()?->id,
            ];
        });
    }

    /**
     * Tetapkan produk katalog global ke cabang saat sidak
     */
    public function assignProductToBranch(string $productId, string $branchId): Stock
    {
        $product = Product::findOrFail($productId);
        $stock = Stock::firstOrCreate([
            'branch_id'  => $branchId,
            'product_id' => $productId,
        ], [
            'quantity_on_hand' => 0,
            'quantity_damaged' => 0,
            'cost_price'       => $product->cost_price,
            'cost_price_tax'   => $product->cost_price_tax,
            'selling_price'    => $product->selling_price,
            'harga_jual_1'     => $product->harga_jual_1 ?? $product->selling_price,
            'is_active'        => true,
            'version'          => 1,
        ]);

        return $stock;
    }

    /**
     * Input atau Update Hasil Hitung Fisik Produk
     */
    public function recordItemCount(
        StockAuditInspection $inspection,
        string $productId,
        float $physicalQty,
        ?string $rackId = null,
        ?string $notes = null
    ): StockAuditInspectionItem {
        $product = Product::findOrFail($productId);
        $stock = Stock::firstOrCreate([
            'branch_id'  => $inspection->branch_id,
            'product_id' => $productId,
        ], [
            'quantity_on_hand' => 0,
            'is_active'        => true,
            'version'          => 1,
        ]);

        $unitCost = (float) ($stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price > 0 ? $stock->cost_price : ($product->cost_price_tax ?? $product->cost_price ?? 0)));
        
        // Murni mengambil data stok dari cabang yang bersangkutan
        $systemQty = (float) $stock->salable_quantity;

        // Selisih murni: Fisik Dihitung - Stok Sistem Cabang saat ini
        $discrepancyQty = $physicalQty - $systemQty;
        $discrepancyCost = $discrepancyQty * $unitCost;

        $status = 'MATCH';
        if ($discrepancyQty < -0.001) {
            $status = 'MINUS';
        } elseif ($discrepancyQty > 0.001) {
            $status = 'PLUS';
        }

        $item = StockAuditInspectionItem::updateOrCreate([
            'inspection_id' => $inspection->id,
            'product_id'    => $productId,
        ], [
            'rack_id'              => $rackId ?: $stock->racks()->first()?->id,
            'system_quantity'      => $systemQty,
            'delta_sales_quantity' => 0,
            'physical_quantity'    => $physicalQty,
            'discrepancy_quantity' => $discrepancyQty,
            'unit_cost'            => $unitCost,
            'discrepancy_cost'     => $discrepancyCost,
            'status'               => $status,
            'clarification_notes'  => $notes,
        ]);

        $inspection->recalculateSummary();

        return $item;
    }

    /**
     * Hapus Item dari Sesi Sidak
     */
    public function removeItem(StockAuditInspection $inspection, string $itemId): void
    {
        $inspection->items()->where('id', $itemId)->delete();
        $inspection->recalculateSummary();
    }

    /**
     * Selesaikan Sesi Sidak
     * @param string $action 'INVESTIGATION' | 'ADJUST' | 'CLOSE'
     */
    public function finishInspection(
        StockAuditInspection $inspection,
        string $action,
        ?string $acknowledgedBy = null,
        ?string $notes = null,
        ?User $user = null
    ): StockAuditInspection {
        return DB::transaction(function () use ($inspection, $action, $acknowledgedBy, $notes, $user) {
            $inspection->update([
                'acknowledged_by' => $acknowledgedBy ?: $inspection->acknowledged_by,
                'notes'           => $notes ?: $inspection->notes,
            ]);

            if ($action === 'ADJUST') {
                $this->applyStockAdjustment($inspection, $user);
                $inspection->update(['status' => 'ADJUSTED']);
            } elseif ($action === 'CLOSE') {
                $inspection->update(['status' => 'CLOSED']);
            } else {
                // INVESTIGATION
                $inspection->update(['status' => 'INVESTIGATION_PENDING']);
            }

            try {
                $this->syncToStockOpnameSession($inspection);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to sync audit {$inspection->audit_number} to StockOpnameSession: " . $e->getMessage());
            }

            return $inspection->fresh(['items.product', 'auditor', 'adjustment']);
        });
    }

    /**
     * Terapkan Koreksi Stok (Stock Adjustment) Otomatis Berdasarkan Temuan Sidak
     */
    public function applyStockAdjustment(StockAuditInspection $inspection, User $user): StockAdjustment
    {
        return DB::transaction(function () use ($inspection, $user) {
            $discrepancyItems = $inspection->items()->where('status', '!=', 'MATCH')->get();
            $adjNumber = 'ADJ-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            $reasonId = \App\Models\AdjustmentReason::where('name', 'like', '%Koreksi%')->first()?->id;

            $adjustment = StockAdjustment::create([
                'adjustment_number'    => $adjNumber,
                'adjustment_date'      => now()->toDateString(),
                'branch_id'            => $inspection->branch_id,
                'adjustment_reason_id' => $reasonId,
                'notes'                => "Penyesuaian Hasil Sidak Audit {$inspection->audit_number}" . ($inspection->notes ? " - {$inspection->notes}" : ''),
                'recorded_by'          => (string) $user->id,
                'status'               => 'COMPLETED',
                'total_value'          => 0,
            ]);

            $totalValue = 0;
            foreach ($discrepancyItems as $item) {
                $stock = Stock::firstOrCreate([
                    'branch_id'  => $inspection->branch_id,
                    'product_id' => $item->product_id,
                ], [
                    'quantity_on_hand' => 0,
                    'is_active'        => true,
                    'version'          => 1,
                ]);

                $beforeQoh = (float) $stock->quantity_on_hand;
                $currentDamaged = (float) ($stock->quantity_damaged ?? 0);
                $newQoh = (float) $item->physical_quantity + $currentDamaged;
                $unitCost = (float) ($stock->cost_price ?? $item->cost_price ?? 0);
                $discrepancy = (float) $item->discrepancy_quantity;
                $totalCost = abs($discrepancy) * $unitCost;
                $totalValue += $totalCost;

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id'          => $item->product_id,
                    'previous_quantity'   => $beforeQoh,
                    'adjustment_quantity' => $discrepancy,
                    'new_quantity'        => $newQoh,
                    'unit_cost'           => $unitCost,
                    'total_cost'          => $totalCost,
                ]);

                // Update stock with Observer metadata for audit log
                $stock->log_type           = $newQoh >= $beforeQoh ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT';
                $stock->reason_code        = 'AUDIT_SIDAK';
                $stock->reference_doc_type = 'STOCK_AUDIT';
                $stock->reference_doc_id   = $inspection->audit_number;
                $stock->notes              = "Hasil Sidak Audit {$inspection->audit_number}: Fisik {$item->physical_quantity} vs Sistem {$item->system_quantity}";
                $stock->recorded_by        = $user->id;

                $stock->update([
                    'quantity_on_hand' => $newQoh,
                    'last_count_date'  => $inspection->inspection_date,
                    'last_so_number'   => $inspection->audit_number,
                ]);
            }

            $adjustment->update(['total_value' => $totalValue]);

            try {
                (new \App\Services\AccountingService())->recordStockAdjustmentJournal($adjustment);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Accounting journal skipped for {$adjNumber}: " . $e->getMessage());
            }

            $inspection->update([
                'status'        => 'ADJUSTED',
                'adjustment_id' => $adjustment->id,
            ]);

            try {
                $this->syncToStockOpnameSession($inspection);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to sync audit {$inspection->audit_number} to StockOpnameSession: " . $e->getMessage());
            }

            return $adjustment;
        });
    }

    /**
     * Sinkronisasi data Sidak Audit ke Sesi Stok Opname agar muncul di Laporan Stok Opname
     */
    public function syncToStockOpnameSession(StockAuditInspection $inspection): StockOpnameSession
    {
        return DB::transaction(function () use ($inspection) {
            $branch = Branch::find($inspection->branch_id);
            $orgId = $branch?->organization_id ?? \App\Models\Organization::first()?->id;

            // Tentukan status Sesi SO berdasarkan status Sidak Audit
            $soStatus = 'FINAL_CHECK';
            if ($inspection->status === 'ADJUSTED') {
                $soStatus = 'COMPLETED';
            } elseif ($inspection->status === 'CLOSED') {
                $soStatus = 'CANCELLED';
            }

            $session = StockOpnameSession::updateOrCreate(
                ['session_number' => $inspection->audit_number],
                [
                    'branch_id'       => $inspection->branch_id,
                    'organization_id' => $orgId,
                    'opname_date'     => $inspection->inspection_date ?? now()->toDateString(),
                    'status'          => $soStatus,
                    'session_token'   => 'SDK-' . Str::random(24),
                    'notes'           => "Hasil Sidak Audit Stok: {$inspection->audit_number}" . ($inspection->notes ? " - {$inspection->notes}" : ''),
                    'created_by'      => $inspection->auditor_id,
                    'approved_by'     => $inspection->status === 'ADJUSTED' ? $inspection->auditor_id : null,
                    'completed_at'    => $inspection->status === 'ADJUSTED' ? ($inspection->updated_at ?? now()) : null,
                ]
            );

            // Pastikan ada rak default SPOT CHECK untuk cabang ini jika item tidak memiliki rak spesifik
            $defaultRack = StockOpnameRack::firstOrCreate(
                [
                    'branch_id' => $inspection->branch_id,
                    'rack_code' => 'SPOT CHECK',
                ],
                [
                    'rack_name' => 'Area Sidak Audit',
                    'is_active' => true,
                ]
            );

            $defaultRackSession = StockOpnameRackSession::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'rack_id'    => $defaultRack->id,
                ],
                [
                    'rack_token'    => 'RACK-' . Str::random(24),
                    'count1_status' => 'DONE',
                    'count2_status' => 'DONE',
                    'count1_by_name'=> $inspection->auditor?->name ?? 'Auditor',
                    'count2_by_name'=> $inspection->witness_name ?: 'Saksi Toko',
                    'count1_at'     => $inspection->created_at,
                    'count2_at'     => $inspection->updated_at,
                ]
            );

            foreach ($inspection->items()->with('product')->get() as $item) {
                $rackId = $item->rack_id;
                $rackSession = $defaultRackSession;

                if ($rackId && $rackId !== $defaultRack->id) {
                    $rackSession = StockOpnameRackSession::firstOrCreate(
                        [
                            'session_id' => $session->id,
                            'rack_id'    => $rackId,
                        ],
                        [
                            'rack_token'    => 'RACK-' . Str::random(24),
                            'count1_status' => 'DONE',
                            'count2_status' => 'DONE',
                            'count1_by_name'=> $inspection->auditor?->name ?? 'Auditor',
                            'count2_by_name'=> $inspection->witness_name ?: 'Saksi Toko',
                            'count1_at'     => $item->created_at,
                            'count2_at'     => $item->updated_at,
                        ]
                    );
                }

                $isMatch = $item->status === 'MATCH';

                if ($inspection->status === 'ADJUSTED') {
                    // Ketika sudah resmi disesuaikan (ADJUSTED):
                    $itemStatus = $isMatch ? 'COUNT2_DONE' : 'FINAL_DONE';
                    $finalQty   = (float) $item->physical_quantity;
                    $c1Qty      = (float) $item->physical_quantity;
                    $c2Qty      = (float) $item->physical_quantity;
                } elseif ($inspection->status === 'CLOSED') {
                    // Ketika ditutup tanpa koreksi (CLOSED):
                    // Selisih pembukuan = 0 (karena tidak ada koreksi fisik / stok sistem tetap)
                    $itemStatus = 'COUNT2_DONE';
                    $finalQty   = (float) $item->system_quantity;
                    $c1Qty      = (float) $item->system_quantity;
                    $c2Qty      = (float) $item->system_quantity;
                } else {
                    // Sedang investigasi (INVESTIGATION_PENDING):
                    // Item cocok selesai, item selisih berstatus DISCREPANCY (belum final)
                    $itemStatus = $isMatch ? 'COUNT2_DONE' : 'DISCREPANCY';
                    $finalQty   = $isMatch ? (float) $item->physical_quantity : null;
                    $c1Qty      = (float) $item->physical_quantity;
                    $c2Qty      = (float) $item->physical_quantity;
                }

                StockOpnameItem::updateOrCreate(
                    [
                        'session_id'      => $session->id,
                        'rack_session_id' => $rackSession->id,
                        'product_id'      => $item->product_id,
                    ],
                    [
                        'system_quantity' => $item->system_quantity,
                        'count1_quantity' => $c1Qty,
                        'count2_quantity' => $c2Qty,
                        'final_quantity'  => $finalQty,
                        'status'          => $itemStatus,
                        'count1_at'       => $item->created_at,
                        'count2_at'       => $item->created_at,
                        'final_at'        => $finalQty !== null ? $item->updated_at : null,
                        'final_by'        => $finalQty !== null ? $inspection->auditor_id : null,
                    ]
                );
            }

            return $session;
        });
    }
}

