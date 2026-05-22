<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Branch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class StockTransferPos extends Component
{
    public $reference_number;
    public $transfer_date;
    public $from_branch_id;
    public $to_branch_id;
    public $notes;
    public $status;

    public $visibleColumns = ['barcode', 'name', 'stock_available', 'qty_transfer', 'unit_price', 'subtotal', 'notes'];

    public $searchQuery = '';
    public $cart = [];

    // Summary
    public $totalQty = 0;
    public $totalLines = 0;
    public $grandTotal = 0;

    public $searchResults = [];

    public $stockTransfer;

    public function mount($stockTransfer = null)
    {
        if ($stockTransfer) {
            $this->stockTransfer = $stockTransfer;
            $this->reference_number = $stockTransfer->reference_number;
            $this->transfer_date = $stockTransfer->transfer_date;
            $this->from_branch_id = $stockTransfer->from_branch_id;
            $this->to_branch_id = $stockTransfer->to_branch_id;
            $this->notes = $stockTransfer->notes;
            $this->status = $stockTransfer->status;

            foreach ($stockTransfer->items as $item) {
                // Get available stock
                $availableStock = 0;
                $stock = \App\Models\Stock::where('branch_id', $this->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($stock) {
                    $availableStock = $stock->quantity_on_hand;
                }

                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode,
                    'name' => $item->product->name,
                    'stock_available' => $availableStock,
                    'qty_transfer' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes
                ];
            }
        } else {
            $this->reference_number = 'TRF-' . strtoupper(uniqid());
            $this->transfer_date = date('Y-m-d');
            $this->from_branch_id = auth()->user()->branch_id ?? Branch::first()?->id;
            $this->to_branch_id = null;
            $this->status = 'pending';
        }

        $this->calculateTotals();
    }

    public function updatedFromBranchId($value)
    {
        $this->cart = [];
        $this->calculateTotals();
    }

    public function updatedSearchQuery($value)
    {
        if (strlen($value) >= 2 && $this->from_branch_id) {
            $this->searchResults = Product::whereHas('stocks', function($q) {
                    $q->where('branch_id', $this->from_branch_id)
                      ->where('quantity_on_hand', '>', 0);
                })
                ->where(function($q) use ($value) {
                    $q->where('sku', 'LIKE', '%' . $value . '%')
                      ->orWhere('barcode', 'LIKE', '%' . $value . '%')
                      ->orWhere('name', 'LIKE', '%' . $value . '%');
                })
                ->limit(5)
                ->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function selectProduct($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $this->addItemToCart($product);
            $this->searchQuery = '';
            $this->searchResults = [];
            $this->dispatch('item-added', index: count($this->cart) - 1);
        }
    }

    public function searchProduct()
    {
        if (!$this->from_branch_id) {
            Notification::make()->title('Pilih Cabang Pengirim terlebih dahulu!')->warning()->send();
            return;
        }

        if (strlen($this->searchQuery) > 0) {
            $product = Product::whereHas('stocks', function($q) {
                    $q->where('branch_id', $this->from_branch_id)
                      ->where('quantity_on_hand', '>', 0);
                })
                ->where(function($q) {
                    $q->where('sku', $this->searchQuery)
                      ->orWhere('barcode', $this->searchQuery)
                      ->orWhere('name', 'LIKE', '%' . $this->searchQuery . '%');
                })
                ->first();

            if ($product) {
                $this->addItemToCart($product);
                $this->searchQuery = '';
                $this->searchResults = [];
                $this->dispatch('item-added', index: count($this->cart) - 1);
            } else {
                Notification::make()->title('Produk tidak ditemukan atau stok kosong di cabang pengirim!')->warning()->send();
            }
        }
    }

    public function addItemToCart($product)
    {
        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $product->id);

        $stock = \App\Models\Stock::where('branch_id', $this->from_branch_id)
            ->where('product_id', $product->id)
            ->first();
        
        $availableStock = $stock ? $stock->quantity_on_hand : 0;

        if ($existingIndex !== false) {
            if ($this->cart[$existingIndex]['qty_transfer'] + 1 > $availableStock) {
                Notification::make()->title('Kuantitas melebihi stok yang tersedia!')->danger()->send();
                return;
            }
            $this->cart[$existingIndex]['qty_transfer']++;
            $this->recalculateRow($existingIndex);
            $this->dispatch('item-added', index: $existingIndex);
        } else {
            if (1 > $availableStock) {
                Notification::make()->title('Stok barang kosong di cabang pengirim!')->danger()->send();
                return;
            }
            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'stock_available' => $availableStock,
                'qty_transfer' => 1,
                'unit_price' => $product->cost_price ?? 0,
                'subtotal' => $product->cost_price ?? 0,
                'notes' => ''
            ];
        }

        $this->calculateTotals();
    }

    public function checkQty($index)
    {
        $qty = (float) $this->cart[$index]['qty_transfer'];
        $available = (float) $this->cart[$index]['stock_available'];
        
        if ($qty > $available) {
            Notification::make()->title("Kuantitas melebihi stok tersedia ({$available})")->danger()->send();
            $this->cart[$index]['qty_transfer'] = $available;
        } elseif ($qty <= 0) {
            $this->cart[$index]['qty_transfer'] = 1;
        }
        
        $this->recalculateRow($index);
        $this->calculateTotals();
    }

    public function recalculateRow($index)
    {
        $item = $this->cart[$index];
        $qty = (float) $item['qty_transfer'];
        $price = (float) $item['unit_price'];
        
        $subtotal = $qty * $price;
        $this->cart[$index]['subtotal'] = round($subtotal, 2);
    }

    public function updateRow($index, $field, $value)
    {
        $this->cart[$index][$field] = $value;
        if ($field === 'qty_transfer') {
            $this->checkQty($index);
        } else if ($field === 'unit_price') {
            $this->recalculateRow($index);
            $this->calculateTotals();
        }
    }

    public function removeItem($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // Re-index
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = collect($this->cart)->sum('qty_transfer');
        $this->grandTotal = collect($this->cart)->sum('subtotal');
    }

    public function save()
    {
        if ($this->stockTransfer && !in_array($this->stockTransfer->status, ['pending', 'rejected'])) {
            Notification::make()->title('Mutasi yang sudah dikirim atau diterima tidak dapat diubah isinya.')->danger()->send();
            return;
        }

        $this->validate([
            'from_branch_id' => 'required',
            'to_branch_id' => 'required|different:from_branch_id',
            'transfer_date' => 'required|date',
            'reference_number' => 'required|unique:stock_transfers,reference_number,' . ($this->stockTransfer ? $this->stockTransfer->id : 'NULL'),
        ], [
            'to_branch_id.different' => 'Cabang asal dan tujuan tidak boleh sama.',
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
        }

        DB::transaction(function () {
            $data = [
                'reference_number' => $this->reference_number,
                'from_branch_id' => $this->from_branch_id,
                'to_branch_id' => $this->to_branch_id,
                'transfer_date' => $this->transfer_date,
                'status' => $this->status ?? 'pending',
                'notes' => $this->notes,
                'total_amount' => $this->grandTotal,
                'created_by' => auth()->id(),
            ];

            if ($this->stockTransfer) {
                // Jangan update created_by jika edit
                unset($data['created_by']);
                $this->stockTransfer->update($data);
                $this->stockTransfer->items()->delete();
                $st = $this->stockTransfer;
            } else {
                $st = StockTransfer::create($data);
            }

            foreach ($this->cart as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $st->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty_transfer'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes']
                ]);
            }
        });

        Notification::make()->title('Mutasi Stok berhasil disimpan.')->success()->send();
        
        return redirect()->to(route('filament.admin.resources.stock-transfers.index'));
    }

    public function render()
    {
        return view('livewire.stock-transfer-pos', [
            'branches' => Branch::all(),
        ]);
    }
}
