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
    public $cetak_nota = false;

    public $visibleColumns = ['barcode', 'name', 'qty_ordered', 'qty_received', 'unit_price', 'harga_jual_1', 'margin_gol_1', 'harga_jual_2', 'margin_gol_2', 'harga_jual_3', 'margin_gol_3', 'discount_1', 'discount_2', 'discount_3', 'subtotal'];

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
                $stock = null;
                if ($this->branch_id) {
                    $stock = Stock::where('product_id', $item->product_id)->where('branch_id', $this->branch_id)->first();
                }

                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode,
                    'name' => $item->product->name,
                    'qty_ordered' => $item->quantity_ordered,
                    'qty_received' => $item->quantity_received,
                    'unit_price' => $item->unit_price,
                    'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($item->product->harga_jual_1 ?? 0),
                    'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($item->product->margin_gol_1 ?? 0),
                    'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($item->product->harga_jual_2 ?? 0),
                    'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($item->product->margin_gol_2 ?? 0),
                    'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($item->product->harga_jual_3 ?? 0),
                    'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($item->product->margin_gol_3 ?? 0),
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

    public $only_latest_po = false;

    public function updatedPurchaseOrderId($value)
    {
        if ($value) {
            $po = PurchaseOrder::with('items.product')->find($value);
            if ($po) {
                $this->supplier_id = $po->supplier_id;
                $this->cart = [];
                foreach ($po->items as $item) {
                    $remainingQty = $item->quantity_ordered - $item->quantity_received;
                    if ($remainingQty <= 0) {
                        continue; // Skip if already fully received
                    }

                    $stock = null;
                    if ($this->branch_id) {
                        $stock = Stock::where('product_id', $item->product_id)->where('branch_id', $this->branch_id)->first();
                    }

                    $this->cart[] = [
                        'product_id' => $item->product_id,
                        'sku' => $item->product->sku,
                        'barcode' => $item->product->barcode,
                        'name' => $item->product->name,
                        'qty_ordered' => $item->quantity_ordered,
                        'qty_received' => $remainingQty, // Default to remaining qty
                        'unit_price' => $item->unit_cost,
                        'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($item->product->harga_jual_1 ?? 0),
                        'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($item->product->margin_gol_1 ?? 0),
                        'discount_1' => $item->discount_1,
                        'discount_2' => $item->discount_2,
                        'discount_3' => $item->discount_3,
                        'subtotal' => $remainingQty * $item->unit_cost // Subtotal uses remaining qty
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
            $stock = null;
            if ($this->branch_id) {
                $stock = Stock::where('product_id', $product->id)->where('branch_id', $this->branch_id)->first();
            }

            $costPrice = ($stock && $stock->cost_price > 0) ? $stock->cost_price : $product->cost_price;

            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'qty_ordered' => 0,
                'qty_received' => 1,
                'unit_price' => $costPrice,
                'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($product->harga_jual_1 ?? 0),
                'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($product->margin_gol_1 ?? 0),
                'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($product->harga_jual_2 ?? 0),
                'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($product->margin_gol_2 ?? 0),
                'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($product->harga_jual_3 ?? 0),
                'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($product->margin_gol_3 ?? 0),
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

    public function updatedCart($value, $name)
    {
        $name = (string) $name;
        $parts = explode('.', $name);
        if (count($parts) === 2) {
            $index = $parts[0];
            $field = $parts[1];
            
            $item = $this->cart[$index];
            $basePrice = (float) ($item['unit_price'] ?? 0);
            $product = \App\Models\Product::find($item['product_id']);
            $price = ($this->include_tax && $product && $product->is_taxable) ? round($basePrice * 1.11, 2) : $basePrice;

            if (in_array($field, ['margin_gol_1', 'margin_gol_2', 'margin_gol_3'])) {
                $gol = substr($field, -1);
                $margin = (float) $value;
                if ($price > 0) {
                    $this->cart[$index]["harga_jual_{$gol}"] = round($price * (1 + ($margin / 100)), 2);
                }
            } elseif (in_array($field, ['harga_jual_1', 'harga_jual_2', 'harga_jual_3'])) {
                $gol = substr($field, -1);
                $sellingPrice = (float) $value;
                if ($price > 0) {
                    $this->cart[$index]["margin_gol_{$gol}"] = round((($sellingPrice - $price) / $price) * 100, 2);
                } else {
                    $this->cart[$index]["margin_gol_{$gol}"] = 100;
                }
            } elseif ($field === 'unit_price') {
                // If unit price changes, keep the selling price the same and recalculate margins
                foreach([1, 2, 3] as $i) {
                    $sellingPrice = (float) ($this->cart[$index]["harga_jual_{$i}"] ?? 0);
                    if ($price > 0) {
                        $this->cart[$index]["margin_gol_{$i}"] = round((($sellingPrice - $price) / $price) * 100, 2);
                    } else {
                        $this->cart[$index]["margin_gol_{$i}"] = 100;
                    }
                }
            }
            
            // Re-calculate subtotals for this row
            $this->recalculateRow($index);
            $this->calculateTotals();
        }
    }

    public function recalculateRow($index)
    {
        $item = $this->cart[$index];
        $qty = (float) ($item['qty_received'] ?? 0);
        $price = (float) ($item['unit_price'] ?? 0);
        
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
        // Recalculate all margins since the cost basis changed
        foreach ($this->cart as $index => $item) {
            $basePrice = (float) ($item['unit_price'] ?? 0);
            $product = \App\Models\Product::find($item['product_id']);
            $price = ($this->include_tax && $product && $product->is_taxable) ? round($basePrice * 1.11, 2) : $basePrice;
            
            foreach([1, 2, 3] as $i) {
                $sellingPrice = (float) ($item["harga_jual_{$i}"] ?? 0);
                if ($price > 0) {
                    $this->cart[$index]["margin_gol_{$i}"] = round((($sellingPrice - $price) / $price) * 100, 2);
                } else {
                    $this->cart[$index]["margin_gol_{$i}"] = 100;
                }
            }
        }
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
            $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
            
            $taxAmount = 0;
            foreach ($this->cart as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                if ($product && $product->is_taxable) {
                    // Apply proportion of global discount to this item
                    $itemProportion = $this->subtotal > 0 ? ($item['subtotal'] / $this->subtotal) : 0;
                    $itemDiscount = $discountAmount * $itemProportion;
                    $itemNet = $item['subtotal'] - $itemDiscount;
                    
                    $taxAmount += round($itemNet * ($taxRate / 100), 2);
                }
            }
            $this->tax_amount = $taxAmount;
        } else {
            $this->tax_amount = 0;
        }

        $this->grandTotal = $netTotal + $this->tax_amount;
    }

    public function save()
    {
        $this->validate([
            'supplier_id' => 'required',
            'branch_id' => 'nullable',
            'receipt_date' => 'required|date',
            'receipt_number' => 'required|unique:goods_receipts,receipt_number,' . ($this->goodsReceipt ? $this->goodsReceipt->id : 'NULL'),
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
        }

        $gr = null;
        DB::transaction(function () use (&$gr) {
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
                
                // Fetch the items collection and delete individually to trigger Eloquent Observers (Stock deduction)
                foreach ($this->goodsReceipt->items as $oldItem) {
                    $oldItem->delete();
                }

                $gr = $this->goodsReceipt;
            } else {
                $gr = GoodsReceipt::create($data);
            }

            $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
            $taxMultiplier = 1 + ($taxRate / 100);

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

                $costPriceTax = $this->include_tax ? round($item['unit_price'] * $taxMultiplier, 2) : $item['unit_price'];

                if (empty($this->branch_id)) {
                    // Update Global Product
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $updateData = [
                            'cost_price' => $item['unit_price'],
                            'cost_price_tax' => $costPriceTax,
                        ];
                        if (auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE')) {
                            $updateData['harga_jual_1'] = $item['harga_jual_1'] ?? $product->harga_jual_1;
                            $updateData['margin_gol_1'] = $item['margin_gol_1'] ?? $product->margin_gol_1;
                            $updateData['selling_price'] = $item['harga_jual_1'] ?? $product->harga_jual_1;
                        }
                        $product->update($updateData);
                    }
                } else {
                    // Update Branch Stock
                    $stock = Stock::where('product_id', $item['product_id'])->where('branch_id', $this->branch_id)->first();
                    if ($stock) {
                        $updateData = [
                            'cost_price' => $item['unit_price'],
                            'cost_price_tax' => $costPriceTax,
                        ];
                        if (auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE')) {
                            $updateData['harga_jual_1'] = $item['harga_jual_1'] ?? $stock->harga_jual_1;
                            $updateData['margin_gol_1'] = $item['margin_gol_1'] ?? $stock->margin_gol_1;
                            $updateData['selling_price'] = $item['harga_jual_1'] ?? $stock->harga_jual_1;
                        }
                        $stock->update($updateData);
                    }
                }
            }

            // Update PO Status if all items received
            if ($this->purchase_order_id) {
                $po = PurchaseOrder::with('items')->find($this->purchase_order_id);
                $allReceived = true;
                foreach ($po->items as $poItem) {
                    // Update quantity_received for each po item based on this receipt
                    $cartItem = collect($this->cart)->firstWhere('product_id', $poItem->product_id);
                    if ($cartItem) {
                        $poItem->quantity_received += $cartItem['qty_received'];
                        $poItem->save();
                    }

                    if ($poItem->quantity_received < $poItem->quantity_ordered) {
                        $allReceived = false;
                    }
                }
                
                if ($allReceived) {
                    $po->update(['status' => 'RECEIVED']);
                } else {
                    $po->update(['status' => 'PARTIALLY_RECEIVED']); // Or keep it APPROVED, but PARTIALLY_RECEIVED is better if it exists. Let's just use PARTIAL if possible, but the requirement only cares if ALL are received.
                }
            }

            // Panggil AccountingService untuk mencatat Jurnal
            $accountingService = new \App\Services\AccountingService();
            $accountingService->recordGoodsReceiptJournal($gr);
        });

        Notification::make()->title('Penerimaan Barang berhasil disimpan dan stok telah diupdate.')->success()->send();
        
        if ($this->cetak_nota && $gr) {
            $printUrl = route('print.document', ['type' => 'receipt', 'ids' => [$gr->id]]);
            $indexUrl = route('filament.admin.resources.goods-receipts.index');
            $this->js("window.open('{$printUrl}', '_blank'); window.location.href = '{$indexUrl}';");
            return;
        }
        
        return redirect()->to(route('filament.admin.resources.goods-receipts.index'));
    }

    public function render()
    {
        $purchaseOrdersQuery = PurchaseOrder::whereIn('status', ['DRAFT', 'SENT', 'APPROVED', 'approved', 'PARTIALLY_RECEIVED', 'partially_received'])
            ->whereHas('items', function ($query) {
                $query->whereColumn('quantity_received', '<', 'quantity_ordered');
            });
        
        if ($this->supplier_id) {
            $purchaseOrdersQuery->where('supplier_id', $this->supplier_id);
            
            if ($this->only_latest_po) {
                $purchaseOrdersQuery->latest('created_at')->limit(1);
            }
        } else {
            $purchaseOrdersQuery->where('id', null); // Don't show any PO if no supplier is selected
        }

        return view('livewire.goods-receipt-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::all(),
            'purchaseOrders' => $purchaseOrdersQuery->get(),
        ]);
    }
}
