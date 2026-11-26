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
    public bool $readOnly = false;

    public function mount($purchaseReturn = null, bool $readOnly = false)
    {
        $this->readOnly = $readOnly;
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
                } else {
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('branch_id', $purchaseReturn->branch_id)
                        ->first();
                    if ($stock) {
                        $phys = (float) $stock->quantity_on_hand;
                        $dmg = max(0.0, (float) ($stock->quantity_damaged ?? 0));
                        $remainingInBatch = max(0.0, $phys - $dmg) + $dmg;
                    }
                }

                $maxQty = $remainingInBatch + (float) $item->quantity;

                $this->cart[] = [
                    'id' => $item->id,
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
            ->with(['stocks' => fn($sq) => $sq->where('branch_id', $this->branch_id)])
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
        $physicalStock = $stock ? (float) $stock->quantity_on_hand : 0.0;
        $damagedStock = $stock ? max(0.0, (float) ($stock->quantity_damaged ?? 0)) : 0.0;
        $salableStock = max(0.0, $physicalStock - $damagedStock);
        // Total fisik yang bisa diretur = stok layak jual + stok karantina BS.
        // Dengan rumus ini, meskipun stok layak jual 0 atau quantity_on_hand <= 0,
        // stok di Karantina BS tetap terhitung dan dapat diretur.
        $returnableStock = $salableStock + $damagedStock;

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

        // Fallback: Jika total stok yang bisa diretur (Layak Jual + Karantina BS) > sisa batch GR aktif supplier ini
        // (Bisa berasal dari Karantina BS yang batch GR-nya sudah habis terjual FIFO, Saldo Awal, Import Stok, atau Mutasi Cabang)
        $nonGrStock = max(0.0, $returnableStock - $totalBatchRemaining);
        if ($nonGrStock > 0 || empty($batchList)) {
            $product = Product::find($productId);
            $hpp = 0.0;
            if ($stock && ((float) $stock->cost_price_tax > 0 || (float) $stock->cost_price > 0)) {
                $hpp = (float) ($stock->cost_price_tax > 0 ? $stock->cost_price_tax : $stock->cost_price);
            } elseif ($product && ((float) $product->cost_price_tax > 0 || (float) $product->cost_price > 0)) {
                $hpp = (float) ($product->cost_price_tax > 0 ? $product->cost_price_tax : $product->cost_price);
            }

            if ($hpp <= 0 && $supplierId) {
                $lastGrItem = GoodsReceiptItem::where('product_id', $productId)
                    ->whereHas('goodsReceipt', function ($q) use ($supplierId, $branchId) {
                        $q->where('supplier_id', $supplierId)
                          ->where('branch_id', $branchId)
                          ->where('status', '!=', 'CANCELLED');
                    })
                    ->latest('id')
                    ->first();
                if ($lastGrItem) {
                    $gr = $lastGrItem->goodsReceipt;
                    $netUnitPrice = $lastGrItem->quantity_received > 0
                        ? ((float) $lastGrItem->subtotal / (float) $lastGrItem->quantity_received)
                        : (float) $lastGrItem->unit_price;
                    $hpp = ($gr && $gr->include_tax) ? round($netUnitPrice * $taxMultiplier, 2) : round($netUnitPrice, 2);
                }
            }

            $maxNonGr = $nonGrStock > 0 ? $nonGrStock : max(0.0, $returnableStock);

            if ($maxNonGr > 0) {
                $nonGrLabel = ($damagedStock > 0 && $salableStock <= 0 && empty($batchList))
                    ? 'KARANTINA BS / NON-GR'
                    : 'NON-GR / SALDO AWAL';

                $batchList[] = [
                    'stock_batch_id' => null,
                    'goods_receipt_id' => null,
                    'gr_number' => $nonGrLabel,
                    'gr_date' => '-',
                    'remaining_qty' => $maxNonGr,
                    'unit_price' => $hpp,
                    'source_type' => 'NON_GR',
                ];
            }
        }

        return [
            'physical_stock' => $physicalStock,
            'damaged_stock' => $damagedStock,
            'salable_stock' => $salableStock,
            'returnable_stock' => $returnableStock,
            'batches' => $batchList,
        ];
    }

    public function addProductToReturn($product, $qtyToAdd = 1)
    {
        $batchInfo = $this->getAvailableBatchesForProduct($product->id);
        $returnableStock = $batchInfo['returnable_stock'];
        $batches = $batchInfo['batches'];

        if ($returnableStock <= 0 && empty($batches)) {
            Notification::make()->title("Stok fisik & karantina BS '{$product->name}' di cabang ini adalah 0!")->warning()->send();
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
        if ($this->readOnly) {
            return;
        }

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

        $noChanges = false;
        DB::transaction(function () use ($validCart, &$pr, &$noChanges) {
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
                unset($data['created_by'], $data['organization_id']);

                $branchChanged = (string) $pr->branch_id !== (string) $this->branch_id;
                $headerChanged = $this->hasHeaderChanges($pr, $data);
                $financialChanged = $this->hasFinancialChanges($pr, $data);

                $oldItems = $pr->items()->get()->keyBy('id');
                $incomingIds = collect($validCart)->pluck('id')->filter()->all();
                $deletedItems = $oldItems->filter(fn ($old) => !in_array($old->id, $incomingIds));

                $toCreate = [];
                $toUpdate = [];
                $anyItemMutated = $branchChanged || $deletedItems->isNotEmpty();

                if ($branchChanged) {
                    $deletedItems = $oldItems;
                    $toCreate = $validCart;
                } else {
                    foreach ($validCart as $item) {
                        $payload = $this->buildReturnItemPayload($item);
                        $itemId = $item['id'] ?? null;
                        $oldItem = $itemId ? $oldItems->get($itemId) : null;

                        if (!$oldItem) {
                            $toCreate[] = $item;
                            $anyItemMutated = true;
                        } elseif ((string) $oldItem->product_id !== (string) $payload['product_id']
                            || (string) ($oldItem->stock_batch_id ?? '') !== (string) ($payload['stock_batch_id'] ?? '')
                            || (string) ($oldItem->goods_receipt_id ?? '') !== (string) ($payload['goods_receipt_id'] ?? '')) {
                            $deletedItems->put($oldItem->id, $oldItem);
                            $toCreate[] = $item;
                            $anyItemMutated = true;
                        } elseif ($this->hasItemChanged($oldItem, $payload)) {
                            $toUpdate[] = ['model' => $oldItem, 'item' => $item, 'payload' => $payload];
                            $anyItemMutated = true;
                        }
                    }
                }

                if (!$headerChanged && !$anyItemMutated) {
                    $noChanges = true;
                    return;
                }

                $oldBranchId = $pr->branch_id;

                foreach ($deletedItems as $oldItem) {
                    $this->revertReturnItemStock($pr, $oldItem, $oldBranchId);
                    $oldItem->delete();
                }

                if ($headerChanged) {
                    $pr->update($data);
                }

                foreach ($toUpdate as $pair) {
                    $oldItem = $pair['model'];
                    $payload = $pair['payload'];

                    $diffQty = (float) $payload['quantity'] - (float) $oldItem->quantity;
                    if (abs($diffQty) > 0.0001) {
                        if (!empty($oldItem->stock_batch_id)) {
                            $batch = StockBatch::find($oldItem->stock_batch_id);
                            if ($batch) {
                                $diffQty > 0
                                    ? $batch->decrement('remaining_quantity', $diffQty)
                                    : $batch->increment('remaining_quantity', abs($diffQty));
                            }
                        } elseif (!empty($oldItem->goods_receipt_id)) {
                            $batch = StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                                ->where('reference_doc_id', $oldItem->goods_receipt_id)
                                ->where('product_id', $oldItem->product_id)
                                ->first();
                            if ($batch) {
                                $diffQty > 0
                                    ? $batch->decrement('remaining_quantity', $diffQty)
                                    : $batch->increment('remaining_quantity', abs($diffQty));
                            }
                        }

                        $stock = Stock::where('product_id', $oldItem->product_id)
                            ->where('branch_id', $this->branch_id)
                            ->first();

                        if ($stock) {
                            if ($diffQty > 0) {
                                $this->deductReturnStock(
                                    $stock,
                                    $pr,
                                    $diffQty,
                                    'Penyesuaian Tambah Retur ' . $pr->return_number,
                                    $pair['item']['gr_number'] ?? null
                                );
                            } else {
                                $this->restoreReturnStock(
                                    $stock,
                                    $pr,
                                    abs($diffQty),
                                    'Penyesuaian Kurang Retur ' . $pr->return_number
                                );
                            }
                        }
                    }

                    $oldItem->update($payload);
                }

                foreach ($toCreate as $item) {
                    $this->createReturnItemAndDeductStock($pr, $item);
                }

                if ($financialChanged || $anyItemMutated) {
                    \App\Models\JournalEntry::where('journalable_id', $pr->id)
                        ->where('journalable_type', PurchaseReturn::class)
                        ->delete();

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

                    $accountingService = new \App\Services\AccountingService();
                    $accountingService->recordPurchaseReturnJournal($pr);
                }
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

                foreach ($validCart as $item) {
                    $this->createReturnItemAndDeductStock($pr, $item);
                }

                // Panggil AccountingService untuk mencatat Jurnal Retur
                $accountingService = new \App\Services\AccountingService();
                $accountingService->recordPurchaseReturnJournal($pr);
            }
        });

        if ($noChanges) {
            Notification::make()->title('Tidak ada perubahan data.')->info()->send();
            return redirect()->to(route('filament.admin.resources.purchase-returns.index'));
        }

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

    private function buildReturnItemPayload(array $item): array
    {
        return [
            'product_id' => $item['product_id'],
            'goods_receipt_id' => $item['goods_receipt_id'] ?? null,
            'stock_batch_id' => $item['stock_batch_id'] ?? null,
            'quantity' => $item['qty_returned'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal'],
            'reason' => $item['reason'] ?? '',
        ];
    }

    private function revertReturnItemStock(PurchaseReturn $pr, PurchaseReturnItem $oldItem, string $branchId): void
    {
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
            ->where('branch_id', $branchId)
            ->first();

        if ($stock) {
            $this->restoreReturnStock(
                $stock,
                $pr,
                (float) $oldItem->quantity,
                'Revert sebelum Edit Retur ' . $pr->return_number
            );
        }
    }

    private function createReturnItemAndDeductStock(PurchaseReturn $pr, array $item): void
    {
        PurchaseReturnItem::create(array_merge(
            ['purchase_return_id' => $pr->id],
            $this->buildReturnItemPayload($item)
        ));

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

        $stock = Stock::where('product_id', $item['product_id'])
            ->where('branch_id', $this->branch_id)
            ->first();

        if ($stock) {
            $this->deductReturnStock(
                $stock,
                $pr,
                (float) $item['qty_returned'],
                'Retur Pembelian ' . $pr->return_number,
                $item['gr_number'] ?? null
            );
        }
    }

    private function deductReturnStock(Stock $stock, PurchaseReturn $pr, float $qtyReturned, string $notePrefix, ?string $grNumber = null): void
    {
        if ($qtyReturned <= 0) {
            return;
        }

        $physicalBefore = (float) $stock->quantity_on_hand;
        $damaged = max(0.0, (float) ($stock->quantity_damaged ?? 0));
        $cutFromDamaged = min($qtyReturned, $damaged);
        $cutFromSalable = $qtyReturned - $cutFromDamaged;

        // Hanya kurangi quantity_on_hand sebesar porsi BS yang memang tercakup di quantity_on_hand positif
        // ditambah porsi dari stok layak jual, agar retur stok BS saat quantity_on_hand <= 0 tidak membuat stok minus.
        $damagedInPhysical = min($damaged, max(0.0, $physicalBefore));
        $unbackedDamaged = max(0.0, $damaged - $damagedInPhysical);
        $qohDeductionFromDamaged = max(0.0, $cutFromDamaged - $unbackedDamaged);
        $totalQohDeduction = $qohDeductionFromDamaged + $cutFromSalable;

        if ($cutFromDamaged > 0) {
            $stock->decrement('quantity_damaged', $cutFromDamaged);

            StockQuarantineMovement::create([
                'organization_id' => $stock->branch->organization_id ?? null,
                'branch_id' => $stock->branch_id,
                'product_id' => $stock->product_id,
                'movement_type' => 'PURCHASE_RETURN',
                'quantity' => $cutFromDamaged,
                'reason' => 'RETUR_KE_SUPPLIER',
                'reference_doc_type' => 'PURCHASE_RETURN',
                'reference_doc_id' => $pr->id,
                'notes' => $notePrefix,
                'created_by' => auth()->id(),
            ]);
        }

        $detailNote = $cutFromDamaged > 0 ? " ({$cutFromDamaged} pcs dari BS, {$cutFromSalable} pcs dari Layak Jual)" : "";
        $fullNotes = $notePrefix . (!empty($grNumber) ? ' (Ref: ' . $grNumber . ')' : '') . $detailNote;

        if ($totalQohDeduction > 0) {
            $stock->log_type = 'RETURN';
            $stock->reason_code = 'PURCHASE_RETURN';
            $stock->reference_doc_type = 'PURCHASE_RETURN';
            $stock->reference_doc_id = $pr->return_number ?: $pr->id;
            $stock->notes = $fullNotes;

            $stock->decrement('quantity_on_hand', $totalQohDeduction);
        } else {
            // Jika quantity_on_hand sudah <= 0 dan murni memotong Karantina BS, tetap catat di Kartu Stok
            \App\Models\InventoryLog::create([
                'branch_id' => $stock->branch_id,
                'product_id' => $stock->product_id,
                'log_type' => 'RETURN',
                'quantity_before' => $physicalBefore,
                'quantity_change' => 0,
                'quantity_after' => $physicalBefore,
                'balance' => $physicalBefore,
                'reason_code' => 'PURCHASE_RETURN',
                'reference_doc_type' => 'PURCHASE_RETURN',
                'reference_doc_id' => $pr->return_number ?: $pr->id,
                'recorded_by' => auth()->id() ?? '00000000-0000-0000-0000-000000000000',
                'notes' => $fullNotes,
            ]);
        }
    }

    private function restoreReturnStock(Stock $stock, PurchaseReturn $pr, float $qtyToRestore, string $notePrefix): void
    {
        if ($qtyToRestore <= 0) {
            return;
        }

        // Cek apakah retur ini sebelumnya memotong stok Karantina BS
        $bsMovements = StockQuarantineMovement::where('reference_doc_type', 'PURCHASE_RETURN')
            ->where('reference_doc_id', $pr->id)
            ->where('product_id', $stock->product_id)
            ->where('branch_id', $stock->branch_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $remainingBsToRestore = $qtyToRestore;
        $restoredToBs = 0.0;

        foreach ($bsMovements as $mov) {
            if ($remainingBsToRestore <= 0) break;
            $movQty = (float) $mov->quantity;
            if ($movQty <= $remainingBsToRestore + 0.0001) {
                $restoredToBs += $movQty;
                $remainingBsToRestore -= $movQty;
                $mov->delete();
            } else {
                $restoredToBs += $remainingBsToRestore;
                $mov->decrement('quantity', $remainingBsToRestore);
                $remainingBsToRestore = 0;
            }
        }

        if ($restoredToBs > 0) {
            $stock->increment('quantity_damaged', $restoredToBs);
        }

        $stock->log_type = 'ADJUSTMENT';
        $stock->reason_code = 'RETURN_EDIT_REVERT';
        $stock->reference_doc_type = 'PURCHASE_RETURN';
        $stock->reference_doc_id = $pr->return_number ?: $pr->id;
        $detailNote = $restoredToBs > 0 ? " ({$restoredToBs} pcs dikembalikan ke BS)" : "";
        $stock->notes = $notePrefix . $detailNote;

        $stock->increment('quantity_on_hand', $qtyToRestore);
    }

    private function hasHeaderChanges(PurchaseReturn $pr, array $data): bool
    {
        foreach (['branch_id', 'supplier_id', 'supplier_division_id', 'goods_receipt_id', 'return_number', 'status', 'notes'] as $field) {
            if ((string) ($pr->{$field} ?? '') !== (string) ($data[$field] ?? '')) {
                return true;
            }
        }

        $oldDate = $pr->return_date instanceof \Carbon\CarbonInterface
            ? $pr->return_date->format('Y-m-d')
            : (string) ($pr->return_date ?? '');
        if ($oldDate !== (string) ($data['return_date'] ?? '')) {
            return true;
        }

        if (abs((float) ($pr->total_amount ?? 0) - (float) ($data['total_amount'] ?? 0)) > 0.01) {
            return true;
        }

        return false;
    }

    private function hasFinancialChanges(PurchaseReturn $pr, array $data): bool
    {
        foreach (['branch_id', 'supplier_id', 'return_number'] as $field) {
            if ((string) ($pr->{$field} ?? '') !== (string) ($data[$field] ?? '')) {
                return true;
            }
        }

        $oldDate = $pr->return_date instanceof \Carbon\CarbonInterface
            ? $pr->return_date->format('Y-m-d')
            : (string) ($pr->return_date ?? '');
        if ($oldDate !== (string) ($data['return_date'] ?? '')) {
            return true;
        }

        if (abs((float) ($pr->total_amount ?? 0) - (float) ($data['total_amount'] ?? 0)) > 0.01) {
            return true;
        }

        return false;
    }

    private function hasItemChanged(PurchaseReturnItem $oldItem, array $payload): bool
    {
        foreach (['quantity', 'unit_price', 'subtotal'] as $field) {
            if (abs((float) ($oldItem->{$field} ?? 0) - (float) ($payload[$field] ?? 0)) > 0.01) {
                return true;
            }
        }

        if ((string) ($oldItem->reason ?? '') !== (string) ($payload['reason'] ?? '')) {
            return true;
        }

        return false;
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
