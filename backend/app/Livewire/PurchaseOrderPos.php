<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Filament\Notifications\Notification;

class PurchaseOrderPos extends Component
{
    use Traits\HasPosDraft;

    public $po_number;
    public $po_date;
    public $expired_date;
    public $faktur;
    public $branch_id;
    public $notes;
    public $supplier_id;
    public $include_tax = false;
    public $tax_amount = 0;
    public $cetak_nota = false;

    public $visibleColumns = ['barcode', 'name', 'avg_bln', 'avg_minggu', 'stock', 'qty_saran', 'qty', 'unit_cost', 'discount_1', 'subtotal'];

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

    public $purchaseOrder;

    public function mount($purchaseOrder = null)
    {
        if ($purchaseOrder) {
            $this->purchaseOrder = $purchaseOrder;
            
            $this->po_number = $purchaseOrder->po_number;
            $this->po_date = $purchaseOrder->po_date;
            $this->expired_date = $purchaseOrder->expired_date;
            $this->faktur = $purchaseOrder->faktur;
            $this->branch_id = $purchaseOrder->branch_id;
            $this->notes = $purchaseOrder->notes;
            $this->supplier_id = $purchaseOrder->supplier_id;
            $this->include_tax = $purchaseOrder->include_tax;
            $this->tax_amount = $purchaseOrder->tax_amount;

            $items = \App\Models\PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->with('product')->get();
            foreach ($items as $item) {
                if (!$item->product) continue;

                // Get stock
                $stock = \App\Models\Stock::where('product_id', $item->product_id)
                    ->where('branch_id', $this->branch_id)
                    ->value('quantity_on_hand') ?? 0;

                $stockModel = $item->product->stocks->where('branch_id', $this->branch_id)->first();

                $avgBln = \App\Models\TransactionItem::where('product_id', $item->product_id)->whereHas('transaction', fn($q) => $q->where('branch_id', $this->branch_id)->where('created_at', '>=', now()->subDays(30)))->sum('quantity');
                $avgMinggu = \App\Models\TransactionItem::where('product_id', $item->product_id)->whereHas('transaction', fn($q) => $q->where('branch_id', $this->branch_id)->where('created_at', '>=', now()->subDays(7)))->sum('quantity');

                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode,
                    'name' => $item->product->name,
                    'max_order' => 0,
                    'avg_bln' => $avgBln,
                    'avg_minggu' => $avgMinggu,
                    'stock' => $stock,
                    'min_qty' => $stockModel->min_qty ?? 0,
                    'max_qty' => $stockModel->max_qty ?? 0,
                    'qty_saran' => (float)($item->quantity_suggested ?? 0),
                    'qty' => (float)$item->quantity_ordered,
                    'unit_cost' => (float)$item->unit_cost,
                    'discount_1' => (float)$item->discount_1,
                    'discount_2' => (float)$item->discount_2,
                    'discount_3' => (float)$item->discount_3,
                    'subtotal' => (float)$item->subtotal
                ];
            }
        } else {
            $this->po_number = 'PO-' . date('YmdHis');
            $this->po_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;

            // Load draft if not editing existing PO
            $this->loadDraft();
        }

        $this->calculateTotals();
    }

    public function dehydrate()
    {
        $this->saveDraft();
    }

    public function updatedSearchQuery($value)
    {
        if (strlen($value) >= 2) {
            $this->searchResults = Product::where('sku', 'LIKE', '%' . $value . '%')
                ->orWhere('barcode', 'LIKE', '%' . $value . '%')
                ->orWhere('name', 'LIKE', '%' . $value . '%')
                ->limit(20)
                ->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function updatedSupplierId($value)
    {
        $this->cart = [];
        $this->calculateTotals();
        
        $supplier = Supplier::find($value);
        if ($supplier && $this->po_date && $supplier->default_po_expired_days > 0) {
            $this->expired_date = \Carbon\Carbon::parse($this->po_date)->addDays($supplier->default_po_expired_days)->format('Y-m-d');
        } else {
            $this->expired_date = null;
        }
    }

    public function updatedPoDate($value)
    {
        if ($this->supplier_id && $value) {
            $supplier = Supplier::find($this->supplier_id);
            if ($supplier && $supplier->default_po_expired_days > 0) {
                $this->expired_date = \Carbon\Carbon::parse($value)->addDays($supplier->default_po_expired_days)->format('Y-m-d');
            }
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
                
                // Dispatch event to focus the new row's Qty input
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
            $this->cart[$existingIndex]['qty']++;
            $this->recalculateRow($existingIndex);
            
            // Dispatch event to focus the row's Qty input
            $this->dispatch('item-added', index: $existingIndex);
        } else {
            $finalCost = $product->cost_price_tax > 0 ? $product->cost_price_tax : ($product->cost_price ?? 0);
            
            $stock = null;
            if ($this->branch_id) {
                $stock = \App\Models\Stock::where('product_id', $product->id)
                    ->where('branch_id', $this->branch_id)
                    ->first();
                
                if ($stock && $stock->cost_price_tax > 0) {
                    $finalCost = $stock->cost_price_tax;
                } elseif ($stock && $stock->cost_price > 0) {
                    $finalCost = $stock->cost_price;
                }
            }

            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'max_order' => 0,
                'avg_bln' => \App\Models\TransactionItem::where('product_id', $product->id)->whereHas('transaction', fn($q) => $q->where('branch_id', $this->branch_id)->where('created_at', '>=', now()->subDays(30)))->sum('quantity'),
                'avg_minggu' => \App\Models\TransactionItem::where('product_id', $product->id)->whereHas('transaction', fn($q) => $q->where('branch_id', $this->branch_id)->where('created_at', '>=', now()->subDays(7)))->sum('quantity'),
                'stock' => $stock ? $stock->quantity_on_hand : 0,
                'min_qty' => $stock ? $stock->min_qty : 0,
                'max_qty' => $stock ? $stock->max_qty : 0,
                'qty_saran' => 0,
                'qty' => 1,
                'unit_cost' => $finalCost,
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'subtotal' => $finalCost
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
        
        $baseTotal = $qty * $cost;
        
        // Apply tiered discounts
        $d1 = $baseTotal * ($item['discount_1'] / 100);
        $t1 = $baseTotal - $d1;
        
        $d2 = $t1 * ($item['discount_2'] / 100);
        $t2 = $t1 - $d2;
        
        $d3 = $t2 * ($item['discount_3'] / 100);
        $subtotal = $t2 - $d3;

        $this->cart[$index]['subtotal'] = round($subtotal, 2);
        $this->calculateTotals();
    }

    public function updatedIncludeTax()
    {
        $this->calculateTotals();
    }

    public function updatedTaxAmount()
    {
        $this->grandTotal = $this->subtotal + (float) $this->tax_amount;
    }

    public function applySaranOrder($method)
    {
        if (!$this->supplier_id) {
            Notification::make()->title('Pilih Supplier terlebih dahulu!')->warning()->send();
            return;
        }

        // Kosongkan keranjang terlebih dahulu agar saran sebelumnya tidak bertumpuk
        $this->cart = [];

        $addedCount = 0;

        if ($method === 'sales') {
            $service = app(\App\Services\SuggestedOrderService::class);
            $suggestions = $service->calculateForBranch($this->branch_id, ['supplier_id' => $this->supplier_id]);
            
            foreach ($suggestions as $suggestion) {
                if ($suggestion['suggested_qty'] > 0) {
                    $product = Product::find($suggestion['product_id']);
                    $this->addItemWithQty($product, $suggestion['suggested_qty'], null, $suggestion['suggested_qty']);
                    $addedCount++;
                }
            }
        } else {
            // Optimization: Eager load stocks for the current branch
            $products = Product::where('supplier_id', $this->supplier_id)
                ->with(['stocks' => function($q) {
                    $q->where('branch_id', $this->branch_id);
                }])
                ->get();

            foreach ($products as $product) {
                $stockRec = $product->stocks->first();
                $currentStock = $stockRec->quantity_on_hand ?? 0;
                $minQty = $stockRec->min_qty ?? 0;
                $maxQty = $stockRec->max_qty ?? 0;

                $suggestedQty = 0;

                if ($method === 'minmax') {
                    if ($currentStock < $minQty) {
                        $suggestedQty = $maxQty - $currentStock;
                    }
                }

                if ($suggestedQty > 0) {
                    $this->addItemWithQty($product, $suggestedQty, $stockRec, $suggestedQty);
                    $addedCount++;
                }
            }
        }

        if ($addedCount > 0) {
            Notification::make()->title("$addedCount barang ditambahkan ke saran order.")->success()->send();
        } else {
            Notification::make()->title("Tidak ada barang yang perlu diorder untuk supplier ini.")->info()->send();
        }
    }

    public function addItemWithQty($product, $qty, $stockRec = null, $qtySaran = 0)
    {
        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $product->id);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty_saran'] = $qtySaran;
            $this->cart[$existingIndex]['qty'] = $qty;
            $this->recalculateRow($existingIndex);
        } else {
            if (!$stockRec && $this->branch_id) {
                $stockRec = \App\Models\Stock::where('product_id', $product->id)
                    ->where('branch_id', $this->branch_id)
                    ->first();
            }

            $finalCost = $product->cost_price_tax > 0 ? $product->cost_price_tax : ($product->cost_price ?? 0);
            if ($stockRec) {
                if ($stockRec->cost_price_tax > 0) {
                    $finalCost = $stockRec->cost_price_tax;
                } elseif ($stockRec->cost_price > 0) {
                    $finalCost = $stockRec->cost_price;
                }
            }

            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'max_order' => 0,
                'avg_bln' => \App\Models\TransactionItem::where('product_id', $product->id)->whereHas('transaction', fn($q) => $q->where('branch_id', $this->branch_id)->where('created_at', '>=', now()->subDays(30)))->sum('quantity'),
                'avg_minggu' => \App\Models\TransactionItem::where('product_id', $product->id)->whereHas('transaction', fn($q) => $q->where('branch_id', $this->branch_id)->where('created_at', '>=', now()->subDays(7)))->sum('quantity'),
                'stock' => $stockRec->quantity_on_hand ?? 0,
                'min_qty' => $stockRec->min_qty ?? 0,
                'max_qty' => $stockRec->max_qty ?? 0,
                'qty_saran' => $qtySaran,
                'qty' => $qty,
                'unit_cost' => $finalCost,
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'subtotal' => $finalCost * $qty
            ];
            
            $this->recalculateRow(count($this->cart) - 1);
        }

        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = collect($this->cart)->sum('qty');
        $this->subtotal = collect($this->cart)->sum('subtotal');

        // Calculate Subtotal Discount
        $discountAmount = 0;
        if ($this->discount_subtotal_type === 'percent') {
            $discountAmount = $this->subtotal * ($this->discount_subtotal / 100);
        } else {
            $discountAmount = (float) $this->discount_subtotal;
        }

        $netTotal = $this->subtotal - $discountAmount;

        $this->tax_amount = 0;
        $this->grandTotal = $netTotal;
    }

    public function save()
    {
        $this->validate([
            'supplier_id' => 'required',
            'branch_id' => 'nullable',
            'po_date' => 'required|date',
            'po_number' => 'required|unique:purchase_orders,po_number,' . ($this->purchaseOrder ? $this->purchaseOrder->id : 'NULL'),
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
        }

        $organization = \App\Models\Organization::find(auth()->user()->organization_id ?? \App\Models\Organization::first()->id);

        $needsApproval = false;
        $approvalReason = [];

        if ($organization) {
            if ($organization->po_approval_limit !== null && $this->grandTotal > $organization->po_approval_limit) {
                $needsApproval = true;
                $approvalReason[] = 'Nominal PO melebihi batas persetujuan: Rp ' . number_format($organization->po_approval_limit, 0, ',', '.');
            }

            if ($organization->po_approval_max_qty_enabled) {
                foreach ($this->cart as $item) {
                    $qtySaran = $item['qty_saran'] ?? 0;

                    if ($item['qty'] > $qtySaran) {
                        $needsApproval = true;
                        $approvalReason[] = "Kuantitas {$item['name']} ({$item['qty']}) melebihi saran order sistem ({$qtySaran}).";
                        break;
                    }
                }
            }
        }

        $status = $needsApproval ? 'pending_approval' : 'approved';
        
        $data = [
            'organization_id' => $organization->id,
            'branch_id' => empty($this->branch_id) ? null : $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'po_number' => $this->po_number,
            'po_date' => $this->po_date,
            'expired_date' => empty($this->expired_date) ? null : $this->expired_date,
            'faktur' => $this->faktur,
            'status' => $status,
            'total_amount' => $this->grandTotal,
            'include_tax' => false,
            'tax_amount' => 0,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ];

        if ($this->purchaseOrder) {
            $this->purchaseOrder->update($data);
            $this->purchaseOrder->items()->delete();
            $po = $this->purchaseOrder;
        } else {
            $po = PurchaseOrder::create($data);
        }

        foreach ($this->cart as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $item['product_id'],
                'quantity_suggested' => $item['qty_saran'] ?? 0,
                'quantity_ordered' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'discount_1' => $item['discount_1'],
                'discount_2' => $item['discount_2'],
                'discount_3' => $item['discount_3'],
                'subtotal' => $item['subtotal']
            ]);
        }

        if ($needsApproval) {
            $po->requestApproval('Otomatis: ' . implode(', ', $approvalReason));
            Notification::make()->title('PO memerlukan persetujuan Manajer.')->warning()->send();
        } else {
            if ($this->purchaseOrder) {
                // Bersihkan approval lama jika PO diedit dan tidak butuh approval lagi
                $po->cancelPendingApprovals();
            }
            Notification::make()->title('Pesanan Pembelian berhasil disimpan.')->success()->send();
        }
        
        $this->clearDraft();

        if ($this->cetak_nota && !$needsApproval) {
            $printUrl = route('print.document', ['type' => 'po', 'ids' => [$po->id]]);
            $indexUrl = route('filament.admin.resources.purchase-orders.index');
            $this->js("window.open('{$printUrl}', '_blank'); window.location.href = '{$indexUrl}';");
            return;
        }

        return redirect()->to(route('filament.admin.resources.purchase-orders.index'));
    }

    public function render()
    {
        return view('livewire.purchase-order-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::all(),
            'topHistory' => PurchaseOrder::where('status', 'DRAFT')->latest()->limit(10)->get()
        ]);
    }
}


