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

    public $totalExpectedBsItems = 0;

    public function mount()
    {
        $this->audit_number = 'AUD-BS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $this->audit_date = date('Y-m-d');

        $userBranchId = Auth::user()?->branch_id;
        $this->branch_id = $userBranchId ?: Branch::where('is_active', true)->first()?->id;

        // Default: Tabel KOSONG agar pengguna bebas melakukan scan fisik barang per barang
        $this->items = [];
        $this->refreshExpectedCount();
        $this->calculateTotals();
    }

    public function refreshExpectedCount()
    {
        if (empty($this->branch_id)) {
            $this->totalExpectedBsItems = 0;
            return;
        }

        $this->totalExpectedBsItems = Stock::where('branch_id', $this->branch_id)
            ->where('quantity_damaged', '>', 0)
            ->count();
    }

    public function updatedBranchId($value)
    {
        $this->items = [];
        $this->refreshExpectedCount();
        $this->calculateTotals();
    }

    public function loadAllBranchBsItems()
    {
        if (empty($this->branch_id)) return;

        $stocks = Stock::where('branch_id', $this->branch_id)
            ->where('quantity_damaged', '>', 0)
            ->with(['product'])
            ->get();

        $this->items = [];
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
                'actual_damaged' => $sys,
                'discrepancy'    => 0,
                'total_cost'     => 0,
                'action'         => 'NONE',
                'notes'          => '',
            ];
        }

        $this->calculateTotals();
        Notification::make()->title("Memuat {$this->totalItemsCount} produk BS dari sistem.")->info()->send();
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

            $products = Product::query()
                ->select(
                    'products.id',
                    'products.name',
                    'products.barcode',
                    'products.sku',
                    'products.unit_of_measure',
                    'products.cost_price_tax',
                    'products.cost_price',
                    'stocks.quantity_damaged'
                )
                ->leftJoin('stocks', function ($join) {
                    $join->on('stocks.product_id', '=', 'products.id')
                         ->where('stocks.branch_id', '=', $this->branch_id);
                })
                ->where('products.is_active', true)
                ->where(function ($q) use ($trimmed, $escaped) {
                    $q->where('products.barcode', $trimmed)
                      ->orWhere('products.sku', $trimmed)
                      ->orWhereJsonContains('products.metadata->additional_barcodes', $trimmed)
                      ->orWhere('products.barcode', 'like', "%{$escaped}%")
                      ->orWhere('products.sku', 'like', "%{$escaped}%")
                      ->orWhere('products.name', 'like', "%{$escaped}%");
                })
                ->orderByRaw("
                    CASE 
                        WHEN products.barcode = ? OR products.sku = ? THEN 1
                        WHEN products.barcode LIKE ? OR products.sku LIKE ? THEN 2
                        WHEN products.name LIKE ? THEN 3
                        ELSE 4
                    END
                ", [$trimmed, $trimmed, "{$escaped}%", "{$escaped}%", "{$escaped}%"])
                ->orderByRaw('COALESCE(stocks.quantity_damaged, 0) DESC')
                ->take(15)
                ->get();

            $formatted = [];
            foreach ($products as $p) {
                $formatted[] = [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'barcode'          => $p->barcode,
                    'sku'              => $p->sku,
                    'unit_of_measure'  => $p->unit_of_measure ?? 'PCS',
                    'cost_price_tax'   => $p->cost_price_tax,
                    'cost_price'       => $p->cost_price,
                    'quantity_damaged' => (float)($p->quantity_damaged ?? 0),
                ];
            }

            $this->searchResults = $formatted;

            // Jika scan barcode exact match (1 hasil persis dengan barcode)
            if (count($formatted) === 1 && ($formatted[0]['barcode'] === $trimmed || $formatted[0]['sku'] === $trimmed)) {
                $this->selectProduct($formatted[0]['id']);
            }
        } else {
            $this->searchResults = [];
        }
    }

    public function handleScanEnter()
    {
        $trimmed = trim((string)$this->searchQuery);
        if (empty($trimmed)) return;

        if (empty($this->searchResults)) {
            $this->updatedSearchQuery($trimmed);
        }

        if (count($this->searchResults) === 1) {
            $this->selectProduct($this->searchResults[0]['id']);
        } elseif (count($this->searchResults) > 1) {
            foreach ($this->searchResults as $res) {
                if ($res['barcode'] === $trimmed || $res['sku'] === $trimmed) {
                    $this->selectProduct($res['id']);
                    return;
                }
            }
            // Pilih hasil pertama
            $this->selectProduct($this->searchResults[0]['id']);
        }
    }

    public function selectProduct($productId)
    {
        $targetIndex = null;

        // Cek jika produk sudah ada di tabel
        foreach ($this->items as $idx => $item) {
            if ($item['product_id'] === $productId) {
                $targetIndex = $idx;
                break;
            }
        }

        if ($targetIndex !== null) {
            // Sudah ada di tabel: fokuskan ke baris tersebut
            $this->searchQuery = '';
            $this->searchResults = [];
            $this->dispatch('focus-row-actual', index: $targetIndex);
            return;
        }

        // Jika belum ada di tabel, tambahkan baris baru
        $product = Product::find($productId);
        if (!$product) return;

        $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $productId)->first();
        $sys = $stock ? (float)($stock->quantity_damaged ?? 0) : 0;
        $cost = $stock ? (float)($stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price ?: 0)) : (float)($product->cost_price ?? 0);

        // Kuantitas awal fisik 1 saat pertama kali discan
        $initialActual = 1.0;
        $diff = $initialActual - $sys;

        $action = 'NONE';
        if ($diff < 0) {
            $action = 'WRITE_OFF_DISPOSAL';
        } elseif ($diff > 0) {
            $action = 'ADJUST_INCREASE';
        }

        $this->items[] = [
            'product_id'     => $product->id,
            'name'           => $product->name,
            'barcode'        => $product->barcode,
            'sku'            => $product->sku,
            'unit'           => $product->unit_of_measure ?? 'PCS',
            'cost_price'     => $cost,
            'system_damaged' => $sys,
            'actual_damaged' => $initialActual,
            'discrepancy'    => $diff,
            'total_cost'     => $diff * $cost,
            'action'         => $action,
            'notes'          => '',
        ];

        $targetIndex = count($this->items) - 1;

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->calculateTotals();

        // Otomatis arahkan kursor ke input qty baris tersebut
        $this->dispatch('focus-row-actual', index: $targetIndex);
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

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->calculateTotals();
        }
    }

    public function clearTable()
    {
        $this->items = [];
        $this->calculateTotals();
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
        Notification::make()->title('Semua fisik pada tabel disamakan dengan saldo sistem BS.')->info()->send();
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
            Notification::make()->title('Tabel audit masih kosong! Silakan scan atau masukkan barang terlebih dahulu.')->warning()->send();
            return;
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id() ?: 1;
            $userOrgId = Auth::user()?->organization_id;

            // 1. Ambil seluruh barang di cabang ini yang punya quantity_damaged > 0
            $existingDamagedStocks = Stock::where('branch_id', $this->branch_id)
                ->where('quantity_damaged', '>', 0)
                ->with('product')
                ->get()
                ->keyBy('product_id');

            $scannedProductIds = collect($this->items)->pluck('product_id')->toArray();

            // 2. Jika ada barang di sistem BS yang TIDAK discan oleh user,
            // berarti barang tersebut memang tidak ada di keranjang BS (Fisik = 0 -> Hilang / Dibuang)
            $finalAuditItems = $this->items;
            foreach ($existingDamagedStocks as $pid => $stock) {
                if (!in_array($pid, $scannedProductIds)) {
                    $cost = (float)($stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price ?: ($stock->product->cost_price ?? 0)));
                    $sys = (float)$stock->quantity_damaged;
                    $diff = -$sys; // Fisik 0 - sistem = minus

                    $finalAuditItems[] = [
                        'product_id'     => $pid,
                        'name'           => $stock->product->name,
                        'barcode'        => $stock->product->barcode,
                        'sku'            => $stock->product->sku,
                        'unit'           => $stock->product->unit_of_measure ?? 'PCS',
                        'cost_price'     => $cost,
                        'system_damaged' => $sys,
                        'actual_damaged' => 0.0,
                        'discrepancy'    => $diff,
                        'total_cost'     => $diff * $cost,
                        'action'         => 'WRITE_OFF_DISPOSAL',
                        'notes'          => 'Tidak ditemukan di keranjang fisik BS (Pemusnahan/Hilang)',
                    ];
                }
            }

            // Hitung total final
            $finalTotalSystem = array_sum(array_column($finalAuditItems, 'system_damaged'));
            $finalTotalActual = array_sum(array_column($finalAuditItems, 'actual_damaged'));
            $finalTotalDisc = array_sum(array_column($finalAuditItems, 'discrepancy'));
            $finalTotalCost = array_sum(array_column($finalAuditItems, 'total_cost'));

            // 3. Simpan Header Audit
            $audit = StockQuarantineAudit::create([
                'id'                    => Str::uuid()->toString(),
                'organization_id'       => $userOrgId,
                'branch_id'             => $this->branch_id,
                'audit_number'          => $this->audit_number,
                'audit_date'            => $this->audit_date,
                'status'                => 'COMPLETED',
                'total_system_damaged'  => $finalTotalSystem,
                'total_actual_damaged'  => $finalTotalActual,
                'total_discrepancy_qty' => $finalTotalDisc,
                'total_discrepancy_cost'=> abs($finalTotalCost),
                'notes'                 => $this->notes ?: 'Audit Berkala Keranjang BS',
                'audited_by'            => $userId,
                'approved_by'           => $userId,
                'completed_at'          => now(),
            ]);

            // 4. Simpan Items & Lakukan Penyesuaian
            foreach ($finalAuditItems as $item) {
                $diff = (float)$item['discrepancy'];
                $action = $item['action'];
                $productId = $item['product_id'];

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
