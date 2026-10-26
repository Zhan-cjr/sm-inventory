<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\SupplierDivision;
use App\Models\Branch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockBatch;
use App\Models\StockQuarantineMovement;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class PurchaseReturnPos extends Component
{
    use Traits\HasPosDraft;
    
    public $return_number;
    public $return_date;
    public $branch_id;
    public $notes;
    public $supplier_id;
    public $supplier_division_id;
    public $goods_receipt_id = null;
    public $cetak_nota = false;
    
    // Search
    public $searchQuery = '';
    public $searchResults = [];

    public $visibleColumns = ['barcode', 'name', 'gr_number', 'max_qty', 'qty_returned', 'unit_price', 'subtotal', 'reason'];

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
            $this->return_date = $purchaseReturn->return_date ? $purchaseReturn->return_date->format('Y-m-d') : date('Y-m-d');
            $this->branch_id = $purchaseReturn->branch_id;
            $this->notes = $purchaseReturn->notes;
            $this->supplier_id = $purchaseReturn->supplier_id;
            $this->supplier_division_id = $purchaseReturn->supplier_division_id;
            $this->goods_receipt_id = $purchaseReturn->goods_receipt_id;
            
            // Populate cart from purchaseReturn->items
            $items = $purchaseReturn->items()->with(['product', 'goodsReceipt', 'stockBatch'])->get();
            foreach ($items as $item) {
                if (!$item->product) continue;

                $effectiveGr = $item->goodsReceipt ?: $purchaseReturn->goodsReceipt;
                $grNumber = $effectiveGr ? $effectiveGr->receipt_number : ($item->goods_receipt_id ? 'GR (Telah Dihapus)' : 'NON-GR / SALDO AWAL');
                $grDate = $effectiveGr && $effectiveGr->receipt_date ? \Carbon\Carbon::parse($effectiveGr->receipt_date)->format('d/m/Y') : '-';

                $remainingInBatch = 0;
                if ($item->stock_batch_id) {
                    $batch = StockBatch::find($item->stock_batch_id);
                    $remainingInBatch = $batch ? (float) $batch->remaining_quantity : 0;
                }

                $maxQty = $remainingInBatch + (float) $item->quantity;

                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'barcode' => $item->product->barcode,
                    'name' => $item->product->name,
                    'stock_batch_id' => $item->stock_batch_id,
                    'goods_receipt_id' => $item->goods_receipt_id,
                    'gr_number' => $grNumber,
                    'gr_date' => $grDate,
                    'source_type' => $item->goods_receipt_id ? 'GR' : 'NON_GR',
                    'max_qty' => $maxQty,
                    'qty_returned' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'reason' => $item->reason ?? '',
                ];
            }
        } else {
            $this->return_number = 'PRT-' . strtoupper(substr(uniqid(), -6));
            $this->return_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? Branch::first()?->id;

            $this->loadDraft();
        }

        $this->calculateTotals();
    }

    public function dehydrate()
    {
        $this->saveDraft();
    }

    public function updatedSupplierId($value)
    {
        $this->supplier_division_id = null;
        $this->cart = [];
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->calculateTotals();
    }

    public function updatedBranchId($value)
    {
        $this->cart = [];
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->calculateTotals();
    }

    public function updatedSearchQuery($value)
    {
        $val = trim((string) $value);
        if (empty($this->supplier_id) || empty($this->branch_id) || strlen($val) < 2) {
            $this->searchResults = [];
            return;
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $val);

        $query = Product::query()
            ->where('is_active', true)
            ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
            ->where(function ($q) use ($escaped) {
                $q->where('barcode', 'like', "%{$escaped}%")
                  ->orWhere('sku', 'like', "%{$escaped}%")
                  ->orWhere('name', 'like', "%{$escaped}%")
                  ->orWhereJsonContains('metadata->additional_barcodes', $escaped);
            });

        $supplierId = $this->supplier_id;

        $query->orderByRaw("
            CASE 
                WHEN supplier_id = ? THEN 1
                WHEN EXISTS (SELECT 1 FROM goods_receipt_items gri JOIN goods_receipts gr ON gri.goods_receipt_id = gr.id WHERE gri.product_id = products.id AND gr.supplier_id = ?) THEN 2
                ELSE 3
            END,
            CASE
                WHEN barcode = ? OR sku = ? THEN 1
                WHEN barcode LIKE ? OR sku LIKE ? THEN 2
                WHEN name LIKE ? THEN 3
                ELSE 4
            END
        ", [$supplierId, $supplierId, $escaped, $escaped, "{$escaped}%", "{$escaped}%", "{$escaped}%"]);

        $this->searchResults = $query->take(20)->get();
    }

    public function searchProduct()
    {
        if (empty($this->supplier_id)) {
            Notification::make()->title('Pilih Supplier terlebih dahulu!')->warning()->send();
            return;
        }
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu!')->warning()->send();
            return;
        }

        $queryStr = trim((string) $this->searchQuery);
        if (strlen($queryStr) === 0) return;

        // 1. Exact match barcode/SKU
        $product = Product::query()
            ->where('is_active', true)
            ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
            ->where(function ($q) use ($queryStr) {
                $q->where('barcode', $queryStr)
                  ->orWhere('sku', $queryStr)
                  ->orWhereJsonContains('metadata->additional_barcodes', $queryStr);
            })
            ->first();

        // 2. Fallback partial match
        if (!$product) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $queryStr);
            $product = Product::query()
                ->where('is_active', true)
                ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
                ->where(function ($q) use ($escaped) {
                    $q->where('barcode', 'like', "%{$escaped}%")
                      ->orWhere('sku', 'like', "%{$escaped}%")
                      ->orWhere('name', 'like', "%{$escaped}%");
                })
                ->first();
        }

        if ($product) {
            $this->addProductToReturn($product);
            $this->searchQuery = '';
            $this->searchResults = [];
        } else {
            Notification::make()->title('Produk tidak ditemukan di cabang ini!')->warning()->send();
        }
    }

    public function selectProduct($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $this->addProductToReturn($product);
            $this->searchQuery = '';
            $this->searchResults = [];
        }
    }

    public function getAvailableBatchesForProduct($productId)
    {
        $branchId = $this->branch_id;
        $supplierId = $this->supplier_id;

        $stock = Stock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();
        $physicalStock = $stock ? (float)$stock->quantity_on_hand : 0;

        $batches = StockBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('reference_doc_type', 'GOODS_RECEIPT')
            ->where('remaining_quantity', '>', 0)
            ->whereIn('reference_doc_id', function ($q) use ($supplierId) {
                $q->select('id')->from('goods_receipts')
                  ->where('supplier_id', $supplierId)
                  ->where('status', '!=', 'CANCELLED');
            })
            ->orderBy('entry_date', 'asc')
            ->get();

        $taxRate = (float) (\App\Models\Organization::first()->tax_rate ?? 11);
        $taxMultiplier = 1 + ($taxRate / 100);

        $batchList = [];
        $totalBatchRemaining = 0;

        foreach ($batches as $batch) {
            $gr = GoodsReceipt::find($batch->reference_doc_id);
            if (!$gr) continue;

            $grItem = GoodsReceiptItem::where('goods_receipt_id', $gr->id)
                ->where('product_id', $productId)
                ->first();

            $remainingQty = (float) $batch->remaining_quantity;
            
            if ($grItem) {
                $netUnitPrice = $grItem->quantity_received > 0 ? ((float)$grItem->subtotal / $grItem->quantity_received) : (float)$grItem->unit_price;
                $unitPriceWithTax = $gr->include_tax ? round($netUnitPrice * $taxMultiplier, 2) : round($netUnitPrice, 2);
            } else {
                $unitPriceWithTax = (float) $batch->cost_price;
            }

            $batchList[] = [
                'stock_batch_id' => $batch->id,
                'goods_receipt_id' => $gr->id,
                'gr_number' => $gr->receipt_number,
                'gr_date' => $gr->receipt_date ? \Carbon\Carbon::parse($gr->receipt_date)->format('d/m/Y') : '',
                'remaining_qty' => $remainingQty,
                'unit_price' => $unitPriceWithTax,
                'source_type' => 'GR',
            ];

            $totalBatchRemaining += $remainingQty;
        }

        // Fallback: Jika stok fisik toko > total sisa batch GR dari supplier ini
        // (Bisa berasal dari Saldo Awal / Import Stok / Mutasi Cabang)
        $nonGrStock = max(0, $physicalStock - $totalBatchRemaining);
        if ($nonGrStock > 0 || empty($batchList)) {
            $product = Product::find($productId);
            $hpp = 0;
            if ($stock && $stock->cost_price > 0) {
                $hpp = (float) ($stock->cost_price_tax > 0 ? $stock->cost_price_tax : $stock->cost_price);
            } elseif ($product && $product->cost_price > 0) {
                $hpp = (float) ($product->cost_price_tax > 0 ? $product->cost_price_tax : $product->cost_price);
            }

            $maxNonGr = $nonGrStock > 0 ? $nonGrStock : max(0, $physicalStock);

            if ($maxNonGr > 0) {
                $batchList[] = [
                    'stock_batch_id' => null,
                    'goods_receipt_id' => null,
                    'gr_number' => 'NON-GR / SALDO AWAL',
                    'gr_date' => '-',
                    'remaining_qty' => $maxNonGr,
                    'unit_price' => $hpp,
                    'source_type' => 'NON_GR',
                ];
            }
        }

        return [
            'physical_stock' => $physicalStock,
            'batches' => $batchList,
        ];
    }

    public function addProductToReturn($product, $qtyToAdd = 1)
    {
        $batchInfo = $this->getAvailableBatchesForProduct($product->id);
        $physicalStock = $batchInfo['physical_stock'];
        $batches = $batchInfo['batches'];

        if ($physicalStock <= 0 && empty($batches)) {
            Notification::make()->title("Stok fisik '{$product->name}' di cabang ini adalah 0!")->warning()->send();
            return;
        }

        if (empty($batches)) {
            Notification::make()->title("Tidak ada stok yang tersedia untuk diretur pada produk ini!")->warning()->send();
            return;
        }

        // Cari batch yang masih punya kapasitas untuk menerima retur
        $targetBatch = null;
        $existingCartIndex = null;

        foreach ($batches as $b) {
            // Cek apakah batch ini sudah ada di cart
            $cartIndex = collect($this->cart)->search(function ($item) use ($product, $b) {
                if ($item['product_id'] != $product->id) return false;
                if ($b['source_type'] === 'GR') {
                    return $item['stock_batch_id'] == $b['stock_batch_id'];
                } else {
                    return $item['source_type'] === 'NON_GR';
                }
            });

            if ($cartIndex !== false) {
                $currentQty = (float) $this->cart[$cartIndex]['qty_returned'];
                $maxQty = (float) $this->cart[$cartIndex]['max_qty'];
                if ($currentQty < $maxQty) {
                    $targetBatch = $b;
                    $existingCartIndex = $cartIndex;
                    break;
                }
            } else {
                // Batch ini belum ada di cart, bisa kita pakai
                $targetBatch = $b;
                $existingCartIndex = null;
                break;
            }
        }

        if ($targetBatch) {
            if ($existingCartIndex !== false && $existingCartIndex !== null) {
                // Tambahkan qty pada baris batch yang sudah ada
                $this->cart[$existingCartIndex]['qty_returned']++;
                $this->recalculateRow($existingCartIndex);
                $this->dispatch('item-added', index: $existingCartIndex);
            } else {
                // Buat baris baru untuk batch ini
                $qty = min($qtyToAdd, $targetBatch['remaining_qty']);
                $newIndex = count($this->cart);
                $this->cart[] = [
                    'product_id' => $product->id,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'stock_batch_id' => $targetBatch['stock_batch_id'],
                    'goods_receipt_id' => $targetBatch['goods_receipt_id'],
                    'gr_number' => $targetBatch['gr_number'],
                    'gr_date' => $targetBatch['gr_date'],
                    'source_type' => $targetBatch['source_type'],
                    'max_qty' => $targetBatch['remaining_qty'],
                    'qty_returned' => $qty,
                    'unit_price' => $targetBatch['unit_price'],
                    'subtotal' => round($qty * $targetBatch['unit_price'], 2),
                    'reason' => '',
                ];
                $this->dispatch('item-added', index: $newIndex);
            }
            $this->calculateTotals();
        } else {
            Notification::make()->title("Semua stok produk '{$product->name}' sudah masuk ke keranjang retur!")->info()->send();
        }
    }

    public function updateRow($index, $field, $value)
    {
        if (!isset($this->cart[$index])) return;

        if ($field === 'qty_returned') {
            $value = (float) $value;
            $max = (float) $this->cart[$index]['max_qty'];
            
            if ($value > $max) {
                // Alokasikan maksimal untuk baris ini
                $excess = $value - $max;
                $this->cart[$index]['qty_returned'] = $max;
                $this->recalculateRow($index);
                
                // Alirkan kelebihan ke batch berikutnya (cascade)
                $productId = $this->cart[$index]['product_id'];
                $reason = $this->cart[$index]['reason'];
                $this->cascadeExcessReturn($productId, $excess, $reason);
            } else {
                if ($value < 0) $value = 0;
                $this->cart[$index]['qty_returned'] = $value;
                $this->recalculateRow($index);
            }
        } elseif ($field === 'unit_price') {
            $this->cart[$index]['unit_price'] = max(0, (float) $value);
            $this->recalculateRow($index);
        } else {
            $this->cart[$index][$field] = $value;
        }

        $this->calculateTotals();
    }

    protected function cascadeExcessReturn($productId, $excessQty, $reason = '')
    {
        if ($excessQty <= 0) return;

        $batchInfo = $this->getAvailableBatchesForProduct($productId);
        $batches = $batchInfo['batches'];
        $product = Product::find($productId);

        foreach ($batches as $b) {
            if ($excessQty <= 0) break;

            // Cari apakah batch ini sudah ada di cart
            $cartIndex = collect($this->cart)->search(function ($item) use ($productId, $b) {
                if ($item['product_id'] != $productId) return false;
                if ($b['source_type'] === 'GR') {
                    return $item['stock_batch_id'] == $b['stock_batch_id'];
                } else {
                    return $item['source_type'] === 'NON_GR';
                }
            });

            if ($cartIndex !== false) {
                $currentQty = (float) $this->cart[$cartIndex]['qty_returned'];
                $maxQty = (float) $this->cart[$cartIndex]['max_qty'];
                $availableRoom = $maxQty - $currentQty;

                if ($availableRoom > 0) {
                    $fill = min($excessQty, $availableRoom);
                    $this->cart[$cartIndex]['qty_returned'] += $fill;
                    if (empty($this->cart[$cartIndex]['reason']) && !empty($reason)) {
                        $this->cart[$cartIndex]['reason'] = $reason;
                    }
                    $this->recalculateRow($cartIndex);
                    $excessQty -= $fill;
                }
            } else {
                // Batch belum ada di cart, buat baris baru
                $fill = min($excessQty, $b['remaining_qty']);
                if ($fill > 0) {
                    $this->cart[] = [
                        'product_id' => $productId,
                        'barcode' => $product->barcode,
                        'name' => $product->name,
                        'stock_batch_id' => $b['stock_batch_id'],
                        'goods_receipt_id' => $b['goods_receipt_id'],
                        'gr_number' => $b['gr_number'],
                        'gr_date' => $b['gr_date'],
                        'source_type' => $b['source_type'],
                        'max_qty' => $b['remaining_qty'],
                        'qty_returned' => $fill,
                        'unit_price' => $b['unit_price'],
                        'subtotal' => round($fill * $b['unit_price'], 2),
                        'reason' => $reason,
                    ];
                    $excessQty -= $fill;
                }
            }
        }

        if ($excessQty > 0) {
            Notification::make()
                ->title("Jumlah retur disesuaikan ke maksimal stok fisik / batch yang tersedia.")
                ->warning()
                ->send();
        }
    }

    public function removeItem($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // Re-index
        $this->calculateTotals();
    }

    public function recalculateRow($index)
    {
        if (!isset($this->cart[$index])) return;
        $item = $this->cart[$index];
        $qty = (float) ($item['qty_returned'] ?? 0);
        $price = (float) ($item['unit_price'] ?? 0);
        
        $this->cart[$index]['subtotal'] = round($qty * $price, 2);
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
            'branch_id' => 'required',
            'return_date' => 'required|date',
        ], [
            'supplier_id.required' => 'Pilih Supplier terlebih dahulu!',
            'branch_id.required' => 'Pilih Lokasi Cabang terlebih dahulu!',
            'return_date.required' => 'Tanggal Retur wajib diisi!',
        ]);

        $validCart = collect($this->cart)->filter(fn($item) => $item['qty_returned'] > 0);

        if ($validCart->isEmpty()) {
            Notification::make()->title('Keranjang Retur kosong! Isi jumlah retur > 0 pada barang yang ingin diretur.')->danger()->send();
            return;
        }

        DB::transaction(function () use ($validCart, &$pr) {
            // Ambil GR pertama jika ada untuk backward compatibility di header (atau null jika multi-GR)
            $firstGrId = $validCart->firstWhere('goods_receipt_id', '!=', null)['goods_receipt_id'] ?? null;

            $data = [
                'organization_id' => \App\Models\Organization::first()->id ?? 1,
                'branch_id' => $this->branch_id,
                'supplier_id' => $this->supplier_id,
                'supplier_division_id' => $this->supplier_division_id,
                'goods_receipt_id' => $firstGrId,
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
                    if ($oldItem->stock_batch_id) {
                        $batch = StockBatch::find($oldItem->stock_batch_id);
                        if ($batch) {
                            $batch->increment('remaining_quantity', $oldItem->quantity);
                        }
                    } elseif ($oldItem->goods_receipt_id) {
                        $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                            ->where('reference_doc_id', $oldItem->goods_receipt_id)
                            ->where('product_id', $oldItem->product_id)
                            ->first();
                        if ($batch) {
                            $batch->increment('remaining_quantity', $oldItem->quantity);
                        }
                    }

                    $stock = Stock::where('product_id', $oldItem->product_id)
                        ->where('branch_id', $pr->branch_id)
                        ->first();
                        
                    if ($stock) {
                        $stock->log_type = 'ADJUSTMENT';
                        $stock->reason_code = 'RETURN_EDIT_REVERT';
                        $stock->reference_doc_type = 'PURCHASE_RETURN';
                        $stock->reference_doc_id = $pr->return_number ?: $pr->id;
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
                    'goods_receipt_id' => $item['goods_receipt_id'] ?? null,
                    'stock_batch_id' => $item['stock_batch_id'] ?? null,
                    'quantity' => $item['qty_returned'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'reason' => $item['reason'] ?? '',
                ]);

                // 1. Kurangi StockBatch jika berasal dari batch GR
                if (!empty($item['stock_batch_id'])) {
                    $batch = StockBatch::find($item['stock_batch_id']);
                    if ($batch) {
                        $batch->decrement('remaining_quantity', $item['qty_returned']);
                    }
                } elseif (!empty($item['goods_receipt_id'])) {
                    $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                        ->where('reference_doc_id', $item['goods_receipt_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();
                    if ($batch) {
                        $batch->decrement('remaining_quantity', $item['qty_returned']);
                    }
                }

                // 2. Kurangi Stok Utama Cabang (Prioritas potong dari Stok Karantina BS terlebih dahulu)
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('branch_id', $this->branch_id)
                    ->first();
                    
                if ($stock) {
                    $qtyReturned = (float)$item['qty_returned'];
                    $damaged = (float)($stock->quantity_damaged ?? 0);
                    $cutFromDamaged = min($qtyReturned, $damaged);
                    $cutFromSalable = $qtyReturned - $cutFromDamaged;

                    if ($cutFromDamaged > 0) {
                        $stock->decrement('quantity_damaged', $cutFromDamaged);

                        // Catat mutasi resmi di riwayat karantina
                        StockQuarantineMovement::create([
                            'organization_id' => $stock->branch->organization_id ?? null,
                            'branch_id' => $stock->branch_id,
                            'product_id' => $stock->product_id,
                            'movement_type' => 'PURCHASE_RETURN',
                            'quantity' => $cutFromDamaged,
                            'reason' => 'RETUR_KE_SUPPLIER',
                            'reference_doc_type' => 'PURCHASE_RETURN',
                            'reference_doc_id' => $pr->id,
                            'notes' => 'Retur Pembelian ' . $pr->return_number,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $stock->log_type = 'RETURN';
                    $stock->reason_code = 'PURCHASE_RETURN';
                    $stock->reference_doc_type = 'PURCHASE_RETURN';
                    $stock->reference_doc_id = $pr->return_number ?: $pr->id;
                    $detailNote = $cutFromDamaged > 0 ? " ({$cutFromDamaged} pcs dari BS, {$cutFromSalable} pcs dari Layak Jual)" : "";
                    $stock->notes = 'Retur Pembelian ' . $pr->return_number . (!empty($item['gr_number']) ? ' (Ref: ' . $item['gr_number'] . ')' : '') . $detailNote;
                    
                    $stock->decrement('quantity_on_hand', $qtyReturned);
                }
            }

            // Panggil AccountingService untuk mencatat Jurnal Retur
            $accountingService = new \App\Services\AccountingService();
            $accountingService->recordPurchaseReturnJournal($pr);
        });

        Notification::make()->title('Retur Pembelian berhasil disimpan.')->success()->send();
        
        $this->clearDraft();

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
        $divisions = [];
        if ($this->supplier_id) {
            $divisions = SupplierDivision::where('supplier_id', $this->supplier_id)->orderBy('name', 'asc')->get();
        }

        return view('livewire.purchase-return-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::where('is_active', true)
                ->when($this->supplier_id, fn($q) => $q->orWhere('id', $this->supplier_id))
                ->orderBy('name', 'asc')
                ->get(),
            'divisions' => $divisions,
        ]);
    }
}
