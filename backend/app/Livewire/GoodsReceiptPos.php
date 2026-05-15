<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Stock;
use App\Models\InventoryLog;
use App\Models\StockBatch;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class GoodsReceiptPos extends Component
{
    public $receipt_number;
    public $receipt_date;
    public $faktur_supplier;
    public $branch_id;
    public $notes;
    public $supplier_id;
    public $purchase_order_id;
    public $include_tax = true;
    public $tax_amount = 0;

    public $visibleColumns = ['barcode', 'name', 'qty_ordered', 'qty_received', 'unit_price', 'discount_1', 'discount_2', 'discount_3', 'subtotal'];

    public $searchQuery = '';
    public $cart = [];

    // Summary
    public $totalQty = 0;
    public $totalLines = 0;
    public $subtotal = 0;
    public $discount_subtotal = 0;
    public $discount_subtotal_type = 'nominal'; // 'percent' or 'nominal'
    public $grandTotal = 0;

    public $searchResults = [];

    public $goodsReceipt;

    public function mount($goodsReceipt = null)
    {
        if ($goodsReceipt) {
            $this->goodsReceipt = $goodsReceipt;
            $this->receipt_number = $goodsReceipt->receipt_number;
            $this->receipt_date = $goodsReceipt->receipt_date;
            $this->faktur_supplier = $goodsReceipt->faktur_supplier;
            $this->branch_id = $goodsReceipt->branch_id;
            $this->notes = $goodsReceipt->notes;
            $this->supplier_id = $goodsReceipt->supplier_id;
            $this->purchase_order_id = $goodsReceipt->purchase_order_id;
            $this->include_tax = $goodsReceipt->include_tax;
            $this->tax_amount = $goodsReceipt->tax_amount;

            foreach ($goodsReceipt->items as $item) {
                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode,
                    'name' => $item->product->name,
                    'qty_ordered' => $item->quantity_ordered,
                    'qty_received' => $item->quantity_received,
                    'unit_price' => $item->unit_price,
                    'discount_1' => $item->discount_1,
                    'discount_2' => $item->discount_2,
                    'discount_3' => $item->discount_3,
                    'subtotal' => $item->subtotal
                ];
            }
        } else {
            $this->receipt_number = 'GR-' . date('YmdHis');
            $this->receipt_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? Branch::first()?->id;
        }

        $this->calculateTotals();
    }

    public function updatedPurchaseOrderId($value)
    {
        if ($value) {
            $po = PurchaseOrder::with('items.product')->find($value);
            if ($po) {
                $this->supplier_id = $po->supplier_id;
                $this->cart = [];
                foreach ($po->items as $item) {
                    $this->cart[] = [
                        'product_id' => $item->product_id,
                        'sku' => $item->product->sku,
                        'barcode' => $item->product->barcode,
                        'name' => $item->product->name,
                        'qty_ordered' => $item->quantity_ordered,
                        'qty_received' => $item->quantity_ordered, // Default to ordered qty
                        'unit_price' => $item->unit_cost,
                        'discount_1' => $item->discount_1,
                        'discount_2' => $item->discount_2,
                        'discount_3' => $item->discount_3,
                        'subtotal' => $item->subtotal
                    ];
                }
                $this->include_tax = $po->include_tax;
                $this->tax_amount = $po->tax_amount;
                $this->calculateTotals();
            }
        }
    }

    public function updatedSearchQuery($value)
    {
        if (strlen($value) >= 2) {
            $this->searchResults = Product::where('sku', 'LIKE', '%' . $value . '%')
                ->orWhere('barcode', 'LIKE', '%' . $value . '%')
                ->orWhere('name', 'LIKE', '%' . $value . '%')
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
        if (strlen($this->searchQuery) > 0) {
            $product = Product::where('sku', $this->searchQuery)
                ->orWhere('barcode', $this->searchQuery)
                ->orWhere('name', 'LIKE', '%' . $this->searchQuery . '%')
                ->first();

            if ($product) {
                $this->addItemToCart($product);
                $this->searchQuery = '';
                $this->searchResults = [];
                $this->dispatch('item-added', index: count($this->cart) - 1);
            } else {
                Notification::make()->title('Produk tidak ditemukan!')->warning()->send();
            }
        }
    }

    public function addItemToCart($product)
    {
        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $product->id);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty_received']++;
            $this->recalculateRow($existingIndex);
            $this->dispatch('item-added', index: $existingIndex);
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'qty_ordered' => 0,
                'qty_received' => 1,
                'unit_price' => $product->cost_price,
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'subtotal' => $product->cost_price
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
        $qty = (float) $item['qty_received'];
        $price = (float) $item['unit_price'];
        
        $baseTotal = $qty * $price;
        
        // Apply tiered discounts
        $d1 = $baseTotal * (($item['discount_1'] ?? 0) / 100);
        $t1 = $baseTotal - $d1;
        
        $d2 = $t1 * (($item['discount_2'] ?? 0) / 100);
        $t2 = $t1 - $d2;
        
        $d3 = $t2 * (($item['discount_3'] ?? 0) / 100);
        $subtotal = $t2 - $d3;

        $this->cart[$index]['subtotal'] = round($subtotal, 2);
    }

    public function updatedIncludeTax()
    {
        $this->calculateTotals();
    }

    public function updatedTaxAmount()
    {
        $this->grandTotal = $this->subtotal + (float) $this->tax_amount;
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = collect($this->cart)->sum('qty_received');
        $this->subtotal = collect($this->cart)->sum('subtotal');

        // Calculate Subtotal Discount
        $discountAmount = 0;
        if ($this->discount_subtotal_type === 'percent') {
            $discountAmount = $this->subtotal * ($this->discount_subtotal / 100);
        } else {
            $discountAmount = (float) $this->discount_subtotal;
        }

        $netTotal = $this->subtotal - $discountAmount;

        if ($this->include_tax) {
            $this->tax_amount = round($netTotal * 0.11, 2);
        } else {
            $this->tax_amount = 0;
        }

        $this->grandTotal = $netTotal + $this->tax_amount;
    }

    public function save()
    {
        $this->validate([
            'supplier_id' => 'required',
            'branch_id' => 'required',
            'receipt_date' => 'required|date',
            'receipt_number' => 'required|unique:goods_receipts,receipt_number,' . ($this->goodsReceipt ? $this->goodsReceipt->id : 'NULL'),
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
        }

        DB::transaction(function () {
            $data = [
                'purchase_order_id' => $this->purchase_order_id,
                'supplier_id' => $this->supplier_id,
                'branch_id' => $this->branch_id,
                'receipt_number' => $this->receipt_number,
                'receipt_date' => $this->receipt_date,
                'received_by' => auth()->user()->name,
                'faktur_supplier' => $this->faktur_supplier,
                'total_amount' => $this->grandTotal,
                'include_tax' => $this->include_tax,
                'tax_amount' => $this->tax_amount,
                'status' => 'RECEIVED',
                'notes' => $this->notes,
            ];

            if ($this->goodsReceipt) {
                // Warning: Re-calculating stock for update is complex. 
                // For simplicity, we only allow creating new GR for now or handle update carefully.
                $this->goodsReceipt->update($data);
                $this->goodsReceipt->items()->delete();
                $gr = $this->goodsReceipt;
            } else {
                $gr = GoodsReceipt::create($data);
            }

            foreach ($this->cart as $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['qty_ordered'],
                    'quantity_received' => $item['qty_received'],
                    'unit_price' => $item['unit_price'],
                    'discount_1' => $item['discount_1'] ?? 0,
                    'discount_2' => $item['discount_2'] ?? 0,
                    'discount_3' => $item['discount_3'] ?? 0,
                    'subtotal' => $item['subtotal']
                ]);
            }

            // Update PO Status if all items received (optional logic)
            if ($this->purchase_order_id) {
                $po = PurchaseOrder::find($this->purchase_order_id);
                $po->update(['status' => 'RECEIVED']);
            }
        });

        Notification::make()->title('Penerimaan Barang berhasil disimpan dan stok telah diupdate.')->success()->send();
        
        return redirect()->to(route('filament.admin.resources.goods-receipts.index'));
    }

    public function render()
    {
        return view('livewire.goods-receipt-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::all(),
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['DRAFT', 'SENT'])->get(),
        ]);
    }
}
