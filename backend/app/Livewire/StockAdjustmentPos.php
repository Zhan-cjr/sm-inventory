<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\AdjustmentReason;
use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Filament\Notifications\Notification;

class StockAdjustmentPos extends Component
{
    use Traits\HasPosDraft;

    public $adjustment_number;
    public $adjustment_date;
    public $branch_id;
    public $notes;
    public $adjustment_reason_id;
    public $reason_type = 'PLUS'; // PLUS or MINUS
    public $cetak_nota = false;

    public $visibleColumns = ['barcode', 'name', 'stock', 'qty', 'unit_cost', 'subtotal'];

    public $searchQuery = '';
    public $cart = [];

    // Summary
    public $totalQty = 0;
    public $totalLines = 0;
    public $grandTotal = 0;

    public $searchResults = [];
    public $stockAdjustment;

    public function mount($stockAdjustment = null)
    {
        if ($stockAdjustment) {
            $this->stockAdjustment = $stockAdjustment;
            
            $this->adjustment_number = $stockAdjustment->adjustment_number;
            $this->adjustment_date = $stockAdjustment->adjustment_date;
            $this->branch_id = $stockAdjustment->branch_id;
            $this->notes = $stockAdjustment->notes;
            $this->adjustment_reason_id = $stockAdjustment->adjustment_reason_id;
            
            $reason = AdjustmentReason::find($this->adjustment_reason_id);
            if ($reason) {
                $this->reason_type = $reason->type;
            }

            $items = StockAdjustmentItem::where('stock_adjustment_id', $stockAdjustment->id)->with('product')->get();
            foreach ($items as $item) {
                if (!$item->product) continue;

                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode,
                    'name' => $item->product->name,
                    'stock' => (float)$item->previous_quantity,
                    'qty' => (float)$item->adjustment_quantity,
                    'new_qty' => (float)$item->new_quantity,
                    'unit_cost' => (float)$item->unit_cost,
                    'subtotal' => (float)$item->total_cost
                ];
            }
        } else {
            $this->adjustment_number = 'ADJ-' . strtoupper(substr(uniqid(), -6));
            $this->adjustment_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;

            // Load draft if not editing existing adjustment
            $this->loadDraft();
            
            $firstReason = AdjustmentReason::first();
            if ($firstReason) {
                $this->adjustment_reason_id = $firstReason->id;
                $this->reason_type = $firstReason->type;
            }
        }

        $this->calculateTotals();
    }

    public function updatedAdjustmentReasonId($value)
    {
        $reason = AdjustmentReason::find($value);
        if ($reason) {
            $this->reason_type = $reason->type;
        }
        foreach ($this->cart as $index => $item) {
            $this->recalculateRow($index);
        }
    }

    public function updatedSearchQuery($value)
    {
        if (empty($this->branch_id)) {
            $this->searchResults = [];
            return;
        }

        if (strlen($value) >= 2) {
            $this->searchResults = Product::query()
                ->select('products.*')
                ->join('stocks', 'stocks.product_id', '=', 'products.id')
                ->where('stocks.branch_id', $this->branch_id)
                ->where('products.is_active', true)
                ->where(function ($q) use ($value) {
                    $q->where('products.barcode', '=', $value)
                      ->orWhere('products.sku', 'LIKE', $value . '%')
                      ->orWhere('products.barcode', 'LIKE', $value . '%')
                      ->orWhere('products.name', 'LIKE', '%' . $value . '%');
                })
                ->limit(20)
                ->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectProduct($productId)
    {
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu!')->warning()->send();
            return;
        }

        $product = Product::query()
            ->select('products.*')
            ->join('stocks', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.branch_id', $this->branch_id)
            ->where('products.is_active', true)
            ->where('products.id', $productId)
            ->first();

        if ($product) {
            $this->addItemToCart($product);
            $this->searchQuery = '';
            $this->searchResults = [];
            $this->dispatch('item-added', index: count($this->cart) - 1);
        }
    }

    public function searchProduct()
    {
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu!')->warning()->send();
            return;
        }

        if (strlen($this->searchQuery) > 0) {
            $queryStr = $this->searchQuery;
            $product = Product::query()
                ->select('products.*')
                ->join('stocks', 'stocks.product_id', '=', 'products.id')
                ->where('stocks.branch_id', $this->branch_id)
                ->where('products.is_active', true)
                ->where(function ($q) use ($queryStr) {
                    $q->where('products.sku', $queryStr)
                      ->orWhere('products.barcode', $queryStr)
                      ->orWhere('products.name', 'LIKE', '%' . $queryStr . '%');
                })
                ->first();

            if ($product) {
                $this->addItemToCart($product);
                $this->searchQuery = '';
                $this->searchResults = [];
                $this->dispatch('item-added', index: count($this->cart) - 1);
            } else {
                Notification::make()->title('Produk aktif tidak ditemukan untuk cabang ini!')->warning()->send();
            }
        }
    }

    public function addItemToCart($product)
    {
        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $product->id);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty']++;
            $this->recalculateRow($existingIndex);
            $this->dispatch('item-added', index: $existingIndex);
        } else {
            // Get stock
            $stockRecord = \App\Models\Stock::where('product_id', $product->id)
                ->where('branch_id', $this->branch_id)
                ->first();

            $stock = $stockRecord ? $stockRecord->quantity_on_hand : 0;
            // Gunakan cost_price_tax dari cabang jika ada, jika tidak fallback ke cost_price_tax produk
            $unitCost = ($stockRecord && $stockRecord->cost_price_tax > 0) ? $stockRecord->cost_price_tax : ($product->cost_price_tax > 0 ? $product->cost_price_tax : $product->cost_price);

            $qty = 1;
            $multiplier = $this->reason_type === 'MINUS' ? -1 : 1;
            $newQty = $stock + ($qty * $multiplier);

            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'stock' => $stock,
                'qty' => $qty,
                'new_qty' => $newQty,
                'unit_cost' => $unitCost,
                'subtotal' => $unitCost * $qty
            ];
        }

        $this->calculateTotals();
    }

    public function updateRow($index, $field, $value)
    {
        $this->cart[$index][$field] = $value;
        $this->recalculateRow($index);
        $this->calculateTotals();
    }

    public function removeItem($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // Re-index
        $this->calculateTotals();
    }

    public function recalculateRow($index)
    {
        $item = $this->cart[$index];
        $qty = (float) $item['qty'];
        $cost = (float) $item['unit_cost'];
        $stock = (float) $item['stock'];
        
        $multiplier = $this->reason_type === 'MINUS' ? -1 : 1;
        $this->cart[$index]['new_qty'] = $stock + ($qty * $multiplier);
        $this->cart[$index]['subtotal'] = round($qty * $cost, 2);
        
        $this->calculateTotals();
    }

    public function dehydrate()
    {
        $this->saveDraft();
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = collect($this->cart)->sum('qty');
        $this->grandTotal = collect($this->cart)->sum('subtotal');
    }

    public function save()
    {
        $this->validate([
            'branch_id' => 'required',
            'adjustment_reason_id' => 'required',
            'adjustment_date' => 'required|date',
            'adjustment_number' => 'required|unique:stock_adjustments,adjustment_number,' . ($this->stockAdjustment ? $this->stockAdjustment->id : 'NULL'),
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
        }

        foreach ($this->cart as $item) {
            if ($item['new_qty'] < 0) {
                Notification::make()
                    ->title('Stok tidak mencukupi!')
                    ->body("Produk {$item['name']} tidak dapat dikurangi {$item['qty']} karena sisa stok hanya {$item['stock']}.")
                    ->danger()
                    ->send();
                return;
            }
        }

        $organization = \App\Models\Organization::find(auth()->user()->organization_id ?? \App\Models\Organization::first()->id);
        
        $needsApproval = false;
        if ($organization && $organization->stock_adjustment_approval_amount_limit !== null) {
            if ($this->grandTotal > $organization->stock_adjustment_approval_amount_limit) {
                $needsApproval = true;
            }
        }

        $status = $needsApproval ? 'PENDING_APPROVAL' : 'COMPLETED';

        $data = [
            'organization_id' => $organization->id,
            'branch_id' => $this->branch_id,
            'adjustment_number' => $this->adjustment_number,
            'adjustment_date' => $this->adjustment_date,
            'adjustment_reason_id' => $this->adjustment_reason_id,
            'notes' => $this->notes,
            'status' => $status,
            'total_value' => $this->grandTotal,
            'recorded_by' => auth()->user()->id, // Storing UUID
        ];

        if ($this->stockAdjustment) {
            $this->stockAdjustment->update($data);
            $this->stockAdjustment->items()->delete();
            $adj = $this->stockAdjustment;
        } else {
            $adj = StockAdjustment::create($data);
        }

        foreach ($this->cart as $item) {
            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adj->id,
                'product_id' => $item['product_id'],
                'previous_quantity' => $item['stock'],
                'adjustment_quantity' => $item['qty'],
                'new_quantity' => $item['new_qty'],
                'unit_cost' => $item['unit_cost'],
                'total_cost' => $item['subtotal']
            ]);
            
            // Execute stock adjustment to actual stock ONLY IF it does not need approval
            if (!$needsApproval) {
                $stockRec = \App\Models\Stock::firstOrCreate([
                    'product_id' => $item['product_id'],
                    'branch_id' => $this->branch_id
                ], [
                    'quantity_on_hand' => 0
                ]);
                $stockRec->quantity_on_hand = $item['new_qty'];
                $stockRec->save();
            }
        }

        if (!$needsApproval) {
            // MENCATAT JURNAL PENYESUAIAN STOK
            $accountingService = new \App\Services\AccountingService();
            $accountingService->recordStockAdjustmentJournal($adj);
        }

        if ($needsApproval) {
            $adj->requestApproval('Otomatis: Nominal koreksi melebihi batas (Rp ' . number_format($organization->stock_adjustment_approval_amount_limit, 0, ',', '.') . ')');
            Notification::make()->title('Koreksi Stok memerlukan persetujuan Manajer.')->warning()->send();
        } else {
            Notification::make()->title('Koreksi Stok berhasil disimpan.')->success()->send();
        }
        
        $this->clearDraft();

        if ($this->cetak_nota && !$needsApproval) {
            $printUrl = route('print.document', ['type' => 'adjustment', 'ids' => [$adj->id]]);
            $indexUrl = route('filament.admin.resources.stock-adjustments.index');
            $this->js("window.open('{$printUrl}', '_blank'); window.location.href = '{$indexUrl}';");
            return;
        }
        
        return redirect()->to(route('filament.admin.resources.stock-adjustments.index'));
    }

    public function render()
    {
        return view('livewire.stock-adjustment-pos', [
            'branches' => Branch::all(),
            'reasons' => AdjustmentReason::all(),
        ]);
    }
}


