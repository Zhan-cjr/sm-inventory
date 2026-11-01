<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\StockQuarantineAudit;
use App\Models\StockQuarantineAuditItem;
use App\Models\StockQuarantineMovement;
use App\Models\InventoryLog;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StockQuarantineAuditPos extends Component
{
    public $audit_number;
    public $audit_date;
    public $branch_id;
    public $notes = '';
    public $cetak_bap = true;

    public $searchQuery = '';
    public $searchResults = [];
    public $items = [];

    public $totalSystem = 0;
    public $totalActual = 0;
    public $totalDiscrepancy = 0;
    public $totalCost = 0;
    public $totalItemsCount = 0;

    public function mount()
    {
        $this->audit_number = 'AUD-BS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $this->audit_date = date('Y-m-d');

        $userBranchId = Auth::user()?->branch_id;
        $this->branch_id = $userBranchId ?: Branch::where('is_active', true)->first()?->id;

        $this->loadBranchBsItems();
    }

    public function loadBranchBsItems()
    {
        $this->items = [];
        if (empty($this->branch_id)) return;

        $stocks = Stock::where('branch_id', $this->branch_id)
            ->where('quantity_damaged', '>', 0)
            ->with(['product'])
            ->get();

        foreach ($stocks as $stock) {
            if (!$stock->product) continue;

            $cost = (float)($stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price ?: ($stock->product->cost_price ?? 0)));
            $sys = (float)$stock->quantity_damaged;

            $this->items[] = [
                'product_id'     => $stock->product_id,
                'name'           => $stock->product->name,
                'barcode'        => $stock->product->barcode,
                'sku'            => $stock->product->sku,
                'unit'           => $stock->product->unit_of_measure ?? 'PCS',
                'cost_price'     => $cost,
                'system_damaged' => $sys,
                'actual_damaged' => $sys, // Default match, operator can edit discrepancy
                'discrepancy'    => 0,
                'total_cost'     => 0,
                'action'         => 'NONE', // NONE, WRITE_OFF_DISPOSAL, RESTORE_TO_SALABLE, ADJUST_INCREASE
                'notes'          => '',
            ];
        }

        $this->calculateTotals();
    }

    public function updatedBranchId($value)
    {
        $this->loadBranchBsItems();
    }

    public function updatedSearchQuery($value)
    {
        if (empty($this->branch_id)) {
            $this->searchResults = [];
            return;
        }

        $trimmed = trim((string)$value);
        if (strlen($trimmed) >= 1) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $trimmed);

            $this->searchResults = Product::query()
                ->select('products.id', 'products.name', 'products.barcode', 'products.sku', 'products.unit_of_measure', 'products.cost_price_tax', 'products.cost_price')
                ->where('products.is_active', true)
                ->where(function ($q) use ($trimmed, $escaped) {
                    $q->where('products.barcode', $trimmed)
                      ->orWhere('products.sku', $trimmed)
                      ->orWhereJsonContains('products.metadata->additional_barcodes', $trimmed)
                      ->orWhere('products.barcode', 'like', "%{$escaped}%")
                      ->orWhere('products.sku', 'like', "%{$escaped}%")
                      ->orWhere('products.name', 'like', "%{$escaped}%");
                })
                ->take(15)
                ->get()
                ->toArray();
        } else {
            $this->searchResults = [];
        }
    }

    public function addProductFromSearch($productId)
    {
        // Check if already in items
        foreach ($this->items as $item) {
            if ($item['product_id'] === $productId) {
                Notification::make()->title('Produk sudah ada di daftar audit!')->warning()->send();
                $this->searchQuery = '';
                $this->searchResults = [];
                return;
            }
        }

        $product = Product::find($productId);
        if (!$product) return;

        $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $productId)->first();
        $sys = $stock ? (float)($stock->quantity_damaged ?? 0) : 0;
        $cost = $stock ? (float)($stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price ?: 0)) : (float)($product->cost_price ?? 0);

        $this->items[] = [
            'product_id'     => $product->id,
            'name'           => $product->name,
            'barcode'        => $product->barcode,
            'sku'            => $product->sku,
            'unit'           => $product->unit_of_measure ?? 'PCS',
            'cost_price'     => $cost,
            'system_damaged' => $sys,
            'actual_damaged' => 1,
            'discrepancy'    => 1 - $sys,
            'total_cost'     => (1 - $sys) * $cost,
            'action'         => 'ADJUST_INCREASE',
            'notes'          => 'Ditemukan di keranjang BS (belum tercatat)',
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->calculateTotals();

        Notification::make()->title("{$product->name} ditambahkan ke audit BS.")->success()->send();
    }

    public function updateActual($index, $val)
    {
        if (!isset($this->items[$index])) return;

        $actual = max(0, (float)$val);
        $this->items[$index]['actual_damaged'] = $actual;

        $sys = (float)$this->items[$index]['system_damaged'];
        $diff = $actual - $sys;
        $this->items[$index]['discrepancy'] = $diff;

        $cost = (float)$this->items[$index]['cost_price'];
        $this->items[$index]['total_cost'] = $diff * $cost;

        // Auto action based on discrepancy
        if ($diff < 0) {
            $this->items[$index]['action'] = 'WRITE_OFF_DISPOSAL';
        } elseif ($diff > 0) {
            $this->items[$index]['action'] = 'ADJUST_INCREASE';
        } else {
            $this->items[$index]['action'] = 'NONE';
        }

        $this->calculateTotals();
    }

    public function setAction($index, $action)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['action'] = $action;
        }
    }

    public function markAllMatch()
    {
        foreach ($this->items as $i => $item) {
            $this->items[$i]['actual_damaged'] = (float)$item['system_damaged'];
            $this->items[$i]['discrepancy'] = 0;
            $this->items[$i]['total_cost'] = 0;
            $this->items[$i]['action'] = 'NONE';
        }
        $this->calculateTotals();
        Notification::make()->title('Semua fisik disamakan dengan saldo sistem BS.')->info()->send();
    }

    public function calculateTotals()
    {
        $this->totalItemsCount = count($this->items);
        $this->totalSystem = array_sum(array_column($this->items, 'system_damaged'));
        $this->totalActual = array_sum(array_column($this->items, 'actual_damaged'));
        $this->totalDiscrepancy = array_sum(array_column($this->items, 'discrepancy'));
        $this->totalCost = array_sum(array_column($this->items, 'total_cost'));
    }

    public function saveAudit()
    {
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih cabang terlebih dahulu!')->danger()->send();
            return;
        }

        if (empty($this->items)) {
            Notification::make()->title('Tidak ada barang di keranjang BS untuk diaudit!')->warning()->send();
            return;
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id() ?: 1;
            $userOrgId = Auth::user()?->organization_id;

            $audit = StockQuarantineAudit::create([
                'id'                    => Str::uuid()->toString(),
                'organization_id'       => $userOrgId,
                'branch_id'             => $this->branch_id,
                'audit_number'          => $this->audit_number,
                'audit_date'            => $this->audit_date,
                'status'                => 'COMPLETED',
                'total_system_damaged'  => $this->totalSystem,
                'total_actual_damaged'  => $this->totalActual,
                'total_discrepancy_qty' => $this->totalDiscrepancy,
                'total_discrepancy_cost'=> abs($this->totalCost),
                'notes'                 => $this->notes ?: 'Audit Berkala Keranjang BS',
                'audited_by'            => $userId,
                'approved_by'           => $userId,
                'completed_at'          => now(),
            ]);

            foreach ($this->items as $item) {
                $diff = (float)$item['discrepancy'];
                $action = $item['action'];
                $productId = $item['product_id'];

                // Save Audit Item
                StockQuarantineAuditItem::create([
                    'id'                 => Str::uuid()->toString(),
                    'audit_id'           => $audit->id,
                    'product_id'         => $productId,
                    'system_damaged_qty' => $item['system_damaged'],
                    'actual_damaged_qty' => $item['actual_damaged'],
                    'discrepancy_qty'    => $diff,
                    'cost_price'         => $item['cost_price'],
                    'total_cost'         => $item['total_cost'],
                    'action_taken'       => $action,
                    'notes'              => $item['notes'] ?: null,
                ]);

                // Apply Stock Adjustments based on Action
                if ($action === 'WRITE_OFF_DISPOSAL' && $diff < 0) {
                    $qtyToCut = abs($diff);
                    $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $productId)->first();
                    if ($stock) {
                        $stock->decrement('quantity_damaged', min($qtyToCut, (float)$stock->quantity_damaged));
                        $stock->decrement('quantity_on_hand', $qtyToCut);

                        StockQuarantineMovement::create([
                            'id'                 => Str::uuid()->toString(),
                            'organization_id'    => $userOrgId,
                            'branch_id'          => $this->branch_id,
                            'product_id'         => $productId,
                            'movement_type'      => 'WRITE_OFF_DISPOSAL',
                            'quantity'           => $qtyToCut,
                            'reason'             => 'PEMUSNAHAN_HASIL_AUDIT_BS',
                            'reference_doc_type' => 'STOCK_QUARANTINE_AUDIT',
                            'reference_doc_id'   => $audit->audit_number,
                            'notes'              => $item['notes'] ?: "Pemusnahan BS fisik hilang/rusak (Audit {$audit->audit_number})",
                            'created_by'         => $userId,
                        ]);

                        InventoryLog::create([
                            'branch_id'          => $this->branch_id,
                            'product_id'         => $productId,
                            'log_type'           => 'ADJUSTMENT_OUT',
                            'quantity_before'    => (float)$stock->quantity_on_hand + $qtyToCut,
                            'quantity_change'    => -$qtyToCut,
                            'quantity_after'     => (float)$stock->quantity_on_hand,
                            'reason_code'        => 'BS_WRITE_OFF',
                            'reference_doc_type' => 'STOCK_QUARANTINE_AUDIT',
                            'reference_doc_id'   => $audit->audit_number,
                            'notes'              => "Pemusnahan BS hilang/dibuang hasil Audit {$audit->audit_number}",
                            'recorded_by'        => (string)$userId,
                        ]);
                    }
                } elseif ($action === 'RESTORE_TO_SALABLE' && $diff < 0) {
                    $qtyToRestore = abs($diff);
                    $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $productId)->first();
                    if ($stock) {
                        $stock->restoreFromQuarantine($qtyToRestore, "Pengembalian ke rak hasil Audit {$audit->audit_number}", $userId);
                    }
                } elseif ($action === 'ADJUST_INCREASE' && $diff > 0) {
                    $qtyToAdd = $diff;
                    $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $productId)->first();
                    if ($stock) {
                        $stock->increment('quantity_damaged', $qtyToAdd);

                        StockQuarantineMovement::create([
                            'id'                 => Str::uuid()->toString(),
                            'organization_id'    => $userOrgId,
                            'branch_id'          => $this->branch_id,
                            'product_id'         => $productId,
                            'movement_type'      => 'MOVE_TO_BS',
                            'quantity'           => $qtyToAdd,
                            'reason'             => 'PENAMBAHAN_HASIL_AUDIT_BS',
                            'reference_doc_type' => 'STOCK_QUARANTINE_AUDIT',
                            'reference_doc_id'   => $audit->audit_number,
                            'notes'              => $item['notes'] ?: "Barang rusak ditemukan saat Audit {$audit->audit_number}",
                            'created_by'         => $userId,
                        ]);
                    }
                }
            }

            DB::commit();

            Notification::make()
                ->title("Audit Keranjang BS {$audit->audit_number} Berhasil!")
                ->body("Hasil audit dan penyesuaian stok BS telah diterapkan secara akurat.")
                ->success()
                ->send();

            if ($this->cetak_bap) {
                $printUrl = route('print.stock-quarantine-audit', ['auditId' => $audit->id]);
                $this->dispatch('open-print-tab', url: $printUrl);
            }

            return redirect()->to('/admin/stock-quarantine-audits');

        } catch (\Throwable $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal menyimpan hasil audit BS!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('livewire.stock-quarantine-audit-pos', [
            'branches' => $branches,
        ]);
    }
}
