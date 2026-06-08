<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockBatch;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class PurchaseReturnPos extends Component
{
    public $return_number;
    public $return_date;
    public $branch_id;
    public $notes;
    public $supplier_id;
    public $goods_receipt_id;
    public $cetak_nota = false;
    
    public $visibleColumns = ['barcode', 'name', 'max_qty', 'qty_returned', 'unit_price', 'subtotal', 'reason'];

    public $cart = [];

    // Summary
    public $totalQty = 0;
    public $totalLines = 0;
    public $grandTotal = 0;

    public $purchaseReturn;

    public function mount($purchaseReturn = null)
    {
        if ($purchaseReturn) {
            $this->purchaseReturn = $purchaseReturn;
            // Mode Edit
            $this->return_number = $purchaseReturn->return_number;
            $this->return_date = $purchaseReturn->return_date;
            $this->branch_id = $purchaseReturn->branch_id;
            $this->notes = $purchaseReturn->notes;
            $this->supplier_id = $purchaseReturn->supplier_id;
            $this->goods_receipt_id = $purchaseReturn->goods_receipt_id;
            
            // Populating cart based on the original GR and the returned items
            $gr = GoodsReceipt::with('items.product')->find($this->goods_receipt_id);
            if ($gr) {
                $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
                $taxMultiplier = $gr->include_tax ? (1 + ($taxRate / 100)) : 1;

                foreach ($gr->items as $item) {
                    $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                        ->where('reference_doc_id', $gr->id)
                        ->where('product_id', $item->product_id)
                        ->first();
                        
                    $remainingQty = $batch ? (float) $batch->remaining_quantity : 0;
                    $returnPrice = round($item->unit_price * $taxMultiplier, 2);

                    // Find if this item was already returned in this PR
                    $prItem = $purchaseReturn->items->where('product_id', $item->product_id)->first();
                    $qtyReturned = $prItem ? (float) $prItem->quantity : 0;
                    $reason = $prItem ? $prItem->reason : '';

                    // The max_qty available to return is the current remaining qty in batch PLUS the qty already returned in this PR
                    $maxQty = $remainingQty + $qtyReturned;

                    if ($maxQty > 0) {
                        $this->cart[] = [
                            'product_id' => $item->product_id,
                            'barcode' => $item->product->barcode,
                            'name' => $item->product->name,
                            'max_qty' => $maxQty,
                            'qty_returned' => $qtyReturned,
                            'unit_price' => $returnPrice,
                            'subtotal' => round($qtyReturned * $returnPrice, 2),
                            'reason' => $reason
                        ];
                    }
                }
            }
        } else {
            $this->return_number = 'PRT-' . strtoupper(substr(uniqid(), -6));
            $this->return_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;
        }

        $this->calculateTotals();
    }

    public function updatedSupplierId($value)
    {
        $this->goods_receipt_id = null;
        $this->cart = [];
        $this->calculateTotals();
    }

    public function updatedGoodsReceiptId($value)
    {
        $this->cart = [];
        if ($value) {
            $gr = GoodsReceipt::with('items.product')->find($value);
            if ($gr) {
                $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
                $taxMultiplier = $gr->include_tax ? (1 + ($taxRate / 100)) : 1;

                foreach ($gr->items as $item) {
                    $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                        ->where('reference_doc_id', $gr->id)
                        ->where('product_id', $item->product_id)
                        ->first();
                        
                    $remainingQty = $batch ? (float) $batch->remaining_quantity : 0;
                    $returnPrice = round($item->unit_price * $taxMultiplier, 2);

                    if ($remainingQty > 0) {
                        $this->cart[] = [
                            'product_id' => $item->product_id,
                            'barcode' => $item->product->barcode,
                            'name' => $item->product->name,
                            'max_qty' => $remainingQty,
                            'qty_returned' => 0,
                            'unit_price' => $returnPrice,
                            'subtotal' => 0,
                            'reason' => ''
                        ];
                    }
                }
            }
        }
        $this->calculateTotals();
    }

    public function updateRow($index, $field, $value)
    {
        if ($field === 'qty_returned') {
            $value = (float) $value;
            $max = (float) $this->cart[$index]['max_qty'];
            if ($value > $max) {
                $value = $max;
            }
            if ($value < 0) {
                $value = 0;
            }
        }
        
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
        $qty = (float) ($item['qty_returned'] ?? 0);
        $price = (float) ($item['unit_price'] ?? 0);
        
        $this->cart[$index]['subtotal'] = round($qty * $price, 2);
        
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = collect($this->cart)->sum('qty_returned');
        $this->grandTotal = collect($this->cart)->sum('subtotal');
    }

    public function save()
    {
        $this->validate([
            'supplier_id' => 'required',
            'goods_receipt_id' => 'required',
            'return_date' => 'required|date',
        ]);

        $validCart = collect($this->cart)->filter(fn($item) => $item['qty_returned'] > 0);

        if ($validCart->isEmpty()) {
            Notification::make()->title('Keranjang Retur kosong! Isi jumlah retur > 0.')->danger()->send();
            return;
        }

        DB::transaction(function () use ($validCart, &$pr) {
            $data = [
                'organization_id' => \App\Models\Organization::first()->id ?? 1,
                'branch_id' => $this->branch_id,
                'supplier_id' => $this->supplier_id,
                'goods_receipt_id' => $this->goods_receipt_id,
                'return_number' => $this->return_number,
                'return_date' => $this->return_date,
                'status' => 'completed',
                'total_amount' => $this->grandTotal,
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ];

            if ($this->purchaseReturn) {
                $pr = $this->purchaseReturn;
                
                // Revert old items' stock
                foreach ($pr->items as $oldItem) {
                    $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                        ->where('reference_doc_id', $pr->goods_receipt_id)
                        ->where('product_id', $oldItem->product_id)
                        ->first();
                        
                    if ($batch) {
                        $batch->increment('remaining_quantity', $oldItem->quantity);
                    }

                    $stock = \App\Models\Stock::where('product_id', $oldItem->product_id)
                        ->where('branch_id', $pr->branch_id)
                        ->first();
                        
                    if ($stock) {
                        $stock->log_type = 'ADJUSTMENT';
                        $stock->reason_code = 'RETURN_EDIT_REVERT';
                        $stock->reference_doc_type = 'PURCHASE_RETURN';
                        $stock->reference_doc_id = $pr->id;
                        $stock->notes = 'Revert sebelum Edit Retur ' . $pr->return_number;
                        
                        $stock->increment('quantity_on_hand', $oldItem->quantity);
                    }
                }
                
                // Delete old items
                $pr->items()->delete();
                
                // Delete old journal entry
                \App\Models\JournalEntry::where('journalable_id', $pr->id)
                    ->where('journalable_type', PurchaseReturn::class)
                    ->delete();
                
                // Update PR header
                $pr->update($data);
                
                \App\Models\SupplierDeduction::updateOrCreate(
                    [
                        'deduction_type' => 'PURCHASE_RETURN',
                        'reference_id' => $pr->id,
                    ],
                    [
                        'supplier_id' => $this->supplier_id,
                        'branch_id' => $this->branch_id,
                        'amount' => $this->grandTotal,
                        'notes' => 'Otomatis dari Retur Pembelian ' . $pr->return_number,
                    ]
                );
            } else {
                $pr = PurchaseReturn::create($data);
                
                \App\Models\SupplierDeduction::create([
                    'supplier_id' => $this->supplier_id,
                    'branch_id' => $this->branch_id,
                    'deduction_type' => 'PURCHASE_RETURN',
                    'reference_id' => $pr->id,
                    'amount' => $this->grandTotal,
                    'claimed_amount' => 0,
                    'status' => 'OPEN',
                    'notes' => 'Otomatis dari Retur Pembelian ' . $pr->return_number,
                ]);
            }

            foreach ($validCart as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $pr->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty_returned'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'reason' => $item['reason'] ?? '',
                ]);

                // 1. Kurangi StockBatch (Sisa pada batch GR tersebut)
                $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                    ->where('reference_doc_id', $this->goods_receipt_id)
                    ->where('product_id', $item['product_id'])
                    ->first();
                    
                if ($batch) {
                    $batch->decrement('remaining_quantity', $item['qty_returned']);
                }

                // 2. Kurangi Stok Utama (Fisik gudang)
                $stock = \App\Models\Stock::where('product_id', $item['product_id'])
                    ->where('branch_id', $this->branch_id)
                    ->first();
                    
                if ($stock) {
                    $stock->log_type = 'RETURN';
                    $stock->reason_code = 'PURCHASE_RETURN';
                    $stock->reference_doc_type = 'PURCHASE_RETURN';
                    $stock->reference_doc_id = $pr->id;
                    $stock->notes = 'Retur Pembelian ' . $pr->return_number;
                    
                    $stock->decrement('quantity_on_hand', $item['qty_returned']);
                }
            }

            // Panggil AccountingService untuk mencatat Jurnal Retur
            $accountingService = new \App\Services\AccountingService();
            $accountingService->recordPurchaseReturnJournal($pr);
        });

        Notification::make()->title('Retur Pembelian berhasil disimpan.')->success()->send();
        
        if ($this->cetak_nota && isset($pr)) {
            $printUrl = route('print.document', ['type' => 'purchase-return', 'ids' => [$pr->id]]);
            $indexUrl = route('filament.admin.resources.purchase-returns.index');
            $this->js("window.open('{$printUrl}', '_blank'); window.location.href = '{$indexUrl}';");
            return;
        }
        
        return redirect()->to(route('filament.admin.resources.purchase-returns.index'));
    }

    public function render()
    {
        $goodsReceiptsQuery = GoodsReceipt::where('status', '!=', 'CANCELLED');
        
        if ($this->supplier_id) {
            $goodsReceiptsQuery->where('supplier_id', $this->supplier_id);
        } else {
            $goodsReceiptsQuery->where('id', null);
        }

        return view('livewire.purchase-return-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::all(),
            'goodsReceipts' => $goodsReceiptsQuery->get(),
        ]);
    }
}


