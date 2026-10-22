<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturnItem;
use App\Models\StockAdjustmentItem;
use App\Models\AdjustmentReason;
use App\Models\GoodsReceiptItem;
use App\Models\TransactionItem;
use App\Models\ProductMonthlySalesHistory;
use Filament\Notifications\Notification;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use App\Services\PromoParserService;

class PurchaseOrderPos extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use Traits\HasPosDraft;
    use WithFileUploads;

    public $po_number;
    public $po_date;
    public $expired_date;
    public $faktur;
    public $branch_id;
    public $notes;
    public $supplier_id;
    public $supplier_division_id;
    public $include_tax = false;
    public $tax_amount = 0;
    public $cetak_nota = false;

    public $visibleColumns = ['barcode', 'name', 'avg_bln', 'avg_minggu', 'stock', 'last_return', 'qty_saran', 'qty', 'unit_cost', 'discount_1', 'subtotal'];

    public $searchQuery = '';
    public $cart = [];

    // Summary
    public $totalQty = 0;
    public $totalLines = 0;
    public $subtotal = 0;
    public $discount_subtotal = 0;
    public $discount_subtotal_type = 'nominal'; // 'percent' or 'nominal'
    public $grandTotal = 0;

    public $showZeroQtyModal = false;
    public $zeroQtyProductNames = '';

    public $searchResults = [];

    public $purchaseOrder;

    // --- PROMO SUPPLIER IMPORT & PASTE ---
    public $showPromoModal = false;
    public $promoSource = 'excel'; // 'excel' or 'text'
    public $promoFile;
    public $promoText = '';
    public $excelSheets = [];
    public $selectedSheet = '';
    public $excelColumns = [];
    public $selectedBarcodeCol = 'A';
    public $selectedPromoCol = 'D';
    public $selectedNameCol = 'C';
    public $startRow = 4;
    public $parsedPromoItems = [];
    public $promoFilterTab = 'all'; // 'all', 'need_order', 'cart_only'
    public $updateExistingCartOnly = false;
    public $promoErrorMessage = '';

    protected $branchEarliestLiveDates = [];

    protected function getBranchEarliestLiveDate(?string $branchId): ?Carbon
    {
        if (!$branchId) return null;
        if (!array_key_exists($branchId, $this->branchEarliestLiveDates)) {
            $earliestTx = \Illuminate\Support\Facades\DB::table('transactions')
                ->where('branch_id', $branchId)
                ->where(function ($q) {
                    $q->where('is_voided', 0)->orWhereNull('is_voided');
                })
                ->min('created_at');
            $this->branchEarliestLiveDates[$branchId] = $earliestTx ? Carbon::parse($earliestTx) : null;
        }
        return $this->branchEarliestLiveDates[$branchId];
    }

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
            $this->supplier_division_id = $purchaseOrder->supplier_division_id;
            $this->include_tax = $purchaseOrder->include_tax;
            $this->tax_amount = $purchaseOrder->tax_amount;

            $items = \App\Models\PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->with('product')->get();
            foreach ($items as $item) {
                if (!$item->product) continue;

                $this->cart[] = $this->resolveCartItemData(
                    $item->product,
                    $this->branch_id,
                    (float)$item->quantity_ordered,
                    (float)($item->quantity_suggested ?? 0),
                    (float)$item->unit_cost,
                    [
                        'discount_1' => (float)$item->discount_1,
                        'discount_2' => (float)$item->discount_2,
                        'discount_3' => (float)$item->discount_3,
                    ],
                    (float)$item->subtotal,
                    $item->promo_note
                );
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
        $value = trim((string) $value);
        if (empty($this->branch_id) || strlen($value) < 2) {
            $this->searchResults = [];
            return;
        }

        // 1. Prioritaskan exact match barcode/SKU di database yang terdaftar di cabang ini
        $exactMatches = Product::query()
            ->where('is_active', true)
            ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
            ->where(function ($q) use ($value) {
                $q->where('barcode', $value)
                  ->orWhere('sku', $value)
                  ->orWhereJsonContains('metadata->additional_barcodes', $value);
            })
            ->take(10)
            ->get();

        if ($exactMatches->isNotEmpty()) {
            $this->searchResults = $exactMatches;
            return;
        }

        // 2. Jika bukan exact match, cari berdasarkan potongan barcode (awal/tengah/akhir), SKU, nama, atau metadata
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $value);
        $this->searchResults = Product::query()
            ->where('is_active', true)
            ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
            ->where(function ($q) use ($escaped) {
                $q->where('barcode', 'like', "%{$escaped}%")
                  ->orWhere('sku', 'like', "%{$escaped}%")
                  ->orWhere('name', 'like', "%{$escaped}%")
                  ->orWhere('metadata', 'like', "%{$escaped}%");
            })
            ->orderByRaw("
                CASE 
                    WHEN barcode LIKE ? OR sku LIKE ? THEN 1
                    WHEN barcode LIKE ? OR sku LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    ELSE 4
                END
            ", ["{$escaped}%", "{$escaped}%", "%{$escaped}%", "%{$escaped}%", "{$escaped}%"])
            ->take(20)
            ->get();
    }

    public function updatedSupplierId($value)
    {
        $this->supplier_division_id = null;
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
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu!')->warning()->send();
            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where('id', $productId)
            ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
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

        $queryStr = trim((string) $this->searchQuery);
        if (strlen($queryStr) > 0) {
            // 1. Prioritaskan Exact Match langsung dari database yang terdaftar di cabang ini
            $product = Product::query()
                ->where('is_active', true)
                ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
                ->where(function ($q) use ($queryStr) {
                    $q->where('barcode', $queryStr)
                      ->orWhere('sku', $queryStr)
                      ->orWhereJsonContains('metadata->additional_barcodes', $queryStr);
                })
                ->first();

            // 2. Jika bukan exact match, gunakan pencarian parsial (awalan / potongan barcode, SKU, nama)
            if (!$product) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $queryStr);
                $product = Product::query()
                    ->where('is_active', true)
                    ->whereHas('stocks', fn($sq) => $sq->where('branch_id', $this->branch_id))
                    ->where(function ($q) use ($escaped) {
                        $q->where('barcode', 'like', "%{$escaped}%")
                          ->orWhere('sku', 'like', "%{$escaped}%")
                          ->orWhere('name', 'like', "%{$escaped}%")
                          ->orWhere('metadata', 'like', "%{$escaped}%");
                    })
                    ->orderByRaw("
                        CASE 
                            WHEN barcode LIKE ? OR sku LIKE ? THEN 1
                            WHEN barcode LIKE ? OR sku LIKE ? THEN 2
                            WHEN name LIKE ? THEN 3
                            ELSE 4
                        END
                    ", ["{$escaped}%", "{$escaped}%", "%{$escaped}%", "%{$escaped}%", "{$escaped}%"])
                    ->first();
            }

            if ($product) {
                $this->addItemToCart($product);
                $this->searchQuery = '';
                $this->searchResults = [];
                
                // Dispatch event to focus the new row's Qty input
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
            
            // Dispatch event to focus the row's Qty input
            $this->dispatch('item-added', index: $existingIndex);
        } else {
            $this->cart[] = $this->resolveCartItemData($product, $this->branch_id, 1, 0);
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
            $suggestions = $service->calculateForBranch($this->branch_id, [
                'supplier_id' => $this->supplier_id,
                'supplier_division_id' => $this->supplier_division_id,
            ]);
            
            foreach ($suggestions as $suggestion) {
                if ($suggestion['suggested_qty'] > 0) {
                    $product = Product::find($suggestion['product_id']);
                    if ($product) {
                        $this->addItemWithQty($product, $suggestion['suggested_qty'], null, $suggestion['suggested_qty']);
                        $addedCount++;
                    }
                }
            }
        } else {
            // Min-Max Method: Query items registered in stocks for THIS branch with effective supplier
            $branchStocks = \App\Models\Stock::where('branch_id', $this->branch_id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('stocks.supplier_id', $this->supplier_id);
                        if ($this->supplier_division_id) {
                            $sq->where('stocks.supplier_division_id', $this->supplier_division_id);
                        }
                    })->orWhere(function ($sq) {
                        $sq->whereNull('stocks.supplier_id')
                           ->whereHas('product', function ($pq) {
                               $pq->where('supplier_id', $this->supplier_id)->where('is_active', true);
                               if ($this->supplier_division_id) {
                                   $pq->where('supplier_division_id', $this->supplier_division_id);
                               }
                           });
                    });
                })
                ->with('product')
                ->get();

            foreach ($branchStocks as $stockRec) {
                $product = $stockRec->product;
                if (!$product) continue;

                $currentStock = (float)($stockRec->quantity_on_hand ?? 0);
                $minQty = (float)($stockRec->min_qty ?? 0);
                $maxQty = (float)($stockRec->max_qty ?? 0);

                $suggestedQty = 0;

                if ($minQty > 0 && $maxQty > 0) {
                    if ($currentStock < $minQty) {
                        $suggestedQty = max(1, $maxQty - $currentStock);
                    }
                }

                if ($suggestedQty > 0) {
                    $this->addItemWithQty($product, $suggestedQty, $stockRec, $suggestedQty);
                    $addedCount++;
                }
            }
        }

        if ($addedCount > 0) {
            $methodName = $method === 'sales' ? 'Analisa Penjualan 30 Hari & AI' : 'Batas Stok Min-Max';
            Notification::make()->title("{$addedCount} barang berhasil ditambahkan berdasarkan {$methodName}.")->success()->send();
        } else {
            Notification::make()->title("Tidak ada barang yang perlu diorder untuk supplier ini.")->info()->send();
        }
    }

    public function addItemWithQty($product, $qty, $stockRec = null, $qtySaran = 0, $customDiscounts = null, $promoNote = null)
    {
        $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $product->id);

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty_saran'] = $qtySaran;
            $this->cart[$existingIndex]['qty'] = $qty;
            if ($customDiscounts) {
                if (isset($customDiscounts['discount_1'])) $this->cart[$existingIndex]['discount_1'] = (float)$customDiscounts['discount_1'];
                if (isset($customDiscounts['discount_2'])) $this->cart[$existingIndex]['discount_2'] = (float)$customDiscounts['discount_2'];
                if (isset($customDiscounts['discount_3'])) $this->cart[$existingIndex]['discount_3'] = (float)$customDiscounts['discount_3'];
            }
            if ($promoNote !== null) {
                $this->cart[$existingIndex]['promo_note'] = $promoNote;
            }
            $this->recalculateRow($existingIndex);
        } else {
            $this->cart[] = $this->resolveCartItemData($product, $this->branch_id, $qty, $qtySaran, null, $customDiscounts, null, $promoNote);
            $this->recalculateRow(count($this->cart) - 1);
        }

        $this->calculateTotals();
    }

    #[Livewire\Attributes\On('removeZeroQtyItems')]
    public function removeZeroQtyItems()
    {
        $this->cart = array_values(array_filter($this->cart, function ($item) {
            return !empty($item['qty']) && (float) $item['qty'] > 0;
        }));
        
        $this->calculateTotals();
        $this->showZeroQtyModal = false;
        Notification::make()->title('Produk dengan qty 0 berhasil dihapus dari keranjang.')->success()->send();
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

        $zeroQtyItems = collect($this->cart)->filter(fn($item) => empty($item['qty']) || (float) $item['qty'] <= 0);
        
        if ($zeroQtyItems->isNotEmpty()) {
            $this->zeroQtyProductNames = $zeroQtyItems->pluck('name')->implode(', ');
            $this->showZeroQtyModal = true;
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
                $overCount = 0;
                foreach ($this->cart as $item) {
                    $qtySaran = $item['qty_saran'] ?? 0;

                    if ($item['qty'] > $qtySaran) {
                        $needsApproval = true;
                        $overCount++;
                        $promoText = !empty($item['promo_note']) ? " [Promo: {$item['promo_note']}]" : ((float)$item['discount_1'] > 0 ? " [Disc: {$item['discount_1']}%]" : "");
                        $stockVal = $item['stock'] ?? 0;
                        $avgSales = $item['avg_bln'] ?? 0;
                        $oosText = !empty($item['is_oos_with_history']) ? " (Pasokan Macet, 90hr: " . (int)$item['sales_90d'] . " pcs)" : "";
                        if ($overCount <= 5) {
                            $approvalReason[] = "{$item['name']} (Order: {$item['qty']} | Saran: {$qtySaran} | Stok: {$stockVal} | Jual: {$avgSales}/bln{$oosText}){$promoText}";
                        }
                    }
                }
                if ($overCount > 5) {
                    $approvalReason[] = "...dan " . ($overCount - 5) . " item lainnya melebihi saran order.";
                }
            }
        }

        $status = $needsApproval ? 'pending_approval' : 'approved';
        
        $data = [
            'organization_id' => $organization->id,
            'branch_id' => empty($this->branch_id) ? null : $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'supplier_division_id' => empty($this->supplier_division_id) ? null : $this->supplier_division_id,
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
            
            // ALWAYS cancel old pending approvals because the document has been modified
            $po->cancelPendingApprovals();
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
                'subtotal' => $item['subtotal'],
                'promo_note' => $item['promo_note'] ?? null,
            ]);
        }

        if ($needsApproval) {
            $po->requestApproval('Otomatis: ' . implode(', ', $approvalReason));
            Notification::make()->title('PO memerlukan persetujuan Manajer.')->warning()->send();
        } else {
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

    public function updatedBranchId($value)
    {
        if (!empty($this->cart)) {
            foreach ($this->cart as $index => $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $refreshed = $this->resolveCartItemData(
                        $product, 
                        $value, 
                        $item['qty'], 
                        $item['qty_saran'] ?? 0, 
                        $item['unit_cost'], 
                        [
                            'discount_1' => $item['discount_1'] ?? 0,
                            'discount_2' => $item['discount_2'] ?? 0,
                            'discount_3' => $item['discount_3'] ?? 0,
                        ],
                        $item['subtotal']
                    );
                    $this->cart[$index] = $refreshed;
                }
            }
        }
    }

    public function getProductReturnInfo($productId, $branchId)
    {
        $ninetyDaysAgo = Carbon::now()->subDays(90)->toDateString();

        // 1. Ambil transaksi retur pembelian terakhir (PurchaseReturn)
        $prItem = PurchaseReturnItem::where('product_id', $productId)
            ->whereHas('purchaseReturn', function ($q) use ($branchId, $ninetyDaysAgo) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                $q->where('return_date', '>=', $ninetyDaysAgo)
                  ->whereIn('status', ['COMPLETED', 'completed', 'APPROVED', 'approved']);
            })
            ->with('purchaseReturn')
            ->latest('created_at')
            ->first();

        // 2. Ambil transaksi koreksi stok retur terakhir (StockAdjustment alasan retur)
        $returReasonIds = AdjustmentReason::where('name', 'like', '%retur%')->pluck('id');
        $adjItem = null;
        if ($returReasonIds->isNotEmpty()) {
            $adjItem = StockAdjustmentItem::where('product_id', $productId)
                ->whereHas('stockAdjustment', function ($q) use ($branchId, $ninetyDaysAgo, $returReasonIds) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                    $q->whereIn('status', ['COMPLETED', 'completed', 'APPROVED', 'approved'])
                      ->whereIn('adjustment_reason_id', $returReasonIds)
                      ->where('adjustment_date', '>=', $ninetyDaysAgo);
                })
                ->with(['stockAdjustment.adjustmentReason'])
                ->latest('created_at')
                ->first();
        }

        // Tentukan mana yang paling baru antara Nota Retur Beli vs Koreksi Stok Retur
        $lastReturn = null;
        if ($prItem && $adjItem) {
            $prDate = $prItem->purchaseReturn->return_date ?? $prItem->created_at;
            $adjDate = $adjItem->stockAdjustment->adjustment_date ?? $adjItem->created_at;
            if (Carbon::parse($prDate)->gte(Carbon::parse($adjDate))) {
                $lastReturn = [
                    'type' => 'retur_beli',
                    'type_label' => 'Nota Retur Supplier',
                    'qty' => (float)$prItem->quantity,
                    'date' => Carbon::parse($prDate)->format('Y-m-d'),
                    'doc_number' => $prItem->purchaseReturn->return_number ?? '-',
                    'reason' => $prItem->reason ?? ($prItem->purchaseReturn->notes ?? '-'),
                ];
            } else {
                $lastReturn = [
                    'type' => 'koreksi_retur',
                    'type_label' => 'Koreksi Stok Retur',
                    'qty' => (float)abs($adjItem->adjustment_quantity),
                    'date' => Carbon::parse($adjDate)->format('Y-m-d'),
                    'doc_number' => $adjItem->stockAdjustment->adjustment_number ?? '-',
                    'reason' => $adjItem->stockAdjustment->adjustmentReason->name ?? ($adjItem->reason_code ?? '-'),
                ];
            }
        } elseif ($prItem) {
            $prDate = $prItem->purchaseReturn->return_date ?? $prItem->created_at;
            $lastReturn = [
                'type' => 'retur_beli',
                'type_label' => 'Nota Retur Supplier',
                'qty' => (float)$prItem->quantity,
                'date' => Carbon::parse($prDate)->format('Y-m-d'),
                'doc_number' => $prItem->purchaseReturn->return_number ?? '-',
                'reason' => $prItem->reason ?? ($prItem->purchaseReturn->notes ?? '-'),
            ];
        } elseif ($adjItem) {
            $adjDate = $adjItem->stockAdjustment->adjustment_date ?? $adjItem->created_at;
            $lastReturn = [
                'type' => 'koreksi_retur',
                'type_label' => 'Koreksi Stok Retur',
                'qty' => (float)abs($adjItem->adjustment_quantity),
                'date' => Carbon::parse($adjDate)->format('Y-m-d'),
                'doc_number' => $adjItem->stockAdjustment->adjustment_number ?? '-',
                'reason' => $adjItem->stockAdjustment->adjustmentReason->name ?? ($adjItem->reason_code ?? '-'),
            ];
        }

        if ($lastReturn) {
            $diffDays = Carbon::parse($lastReturn['date'])->diffInDays(now());
            $lastReturn['days_ago'] = (int)$diffDays;
            $lastReturn['date_formatted'] = Carbon::parse($lastReturn['date'])->format('d/m/Y');
            $lastReturn['formatted'] = number_format($lastReturn['qty'], 0) . ' pcs (' . ($diffDays == 0 ? 'hari ini' : $diffDays . ' hr lalu') . ')';
        }

        return $lastReturn;
    }

    public function resolveCartItemData($product, $branchId, $qty = 1, $qtySaran = 0, $customCost = null, $customDiscounts = null, $customSubtotal = null, $promoNote = null)
    {
        $stock = null;
        $stockModel = null;
        if ($branchId) {
            $stockModel = Stock::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->first();
            $stock = $stockModel ? (float)$stockModel->quantity_on_hand : 0;
        }

        if ($customCost !== null) {
            $finalCost = (float)$customCost;
        } else {
            $finalCost = (float)($product->cost_price_tax > 0 ? $product->cost_price_tax : ($product->cost_price ?? 0));
            if ($stockModel) {
                if ($stockModel->cost_price_tax > 0) {
                    $finalCost = (float)$stockModel->cost_price_tax;
                } elseif ($stockModel->cost_price > 0) {
                    $finalCost = (float)$stockModel->cost_price;
                }
            }
        }

        $refDate = $this->po_date ? Carbon::parse($this->po_date) : now();
        $thirtyDaysAgo = $refDate->copy()->subDays(30);
        $ninetyDaysAgo = $refDate->copy()->subDays(90);
        $sevenDaysAgo = $refDate->copy()->subDays(7);

        $earliestLive = $this->getBranchEarliestLiveDate($branchId);

        // 1. Hitung Penjualan 30 Hari dan 7 Hari (Continuous Time-Series Stitching)
        $avgBln = 0.0;
        $avgMinggu = 0.0;
        $isSalesFromHistory = false;

        if ($earliestLive && $earliestLive->lte($thirtyDaysAgo)) {
            // Kasus A: Cabang sudah aktif di SM Inventory >= 30 hari. 100% data live kasir!
            $avgBln = (float)TransactionItem::where('product_id', $product->id)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->whereBetween('created_at', [$thirtyDaysAgo, $refDate]))
                ->sum('quantity');

            $avgMinggu = (float)TransactionItem::where('product_id', $product->id)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->whereBetween('created_at', [$sevenDaysAgo, $refDate]))
                ->sum('quantity');
        } elseif ($earliestLive) {
            // Kasus B: Cabang aktif di SM Inventory < 30 hari. Sambungkan live + bulan sebelum migrasi dari Excel!
            $liveSales = (float)TransactionItem::where('product_id', $product->id)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->whereBetween('created_at', [$earliestLive, $refDate]))
                ->sum('quantity');

            $preLiveCutoff = $earliestLive->copy()->startOfMonth();
            $histMonth = $refDate->copy()->subMonth();
            $histSales = 0.0;
            if ($histMonth->lt($preLiveCutoff)) {
                $histSales = (float)ProductMonthlySalesHistory::where('branch_id', $branchId)
                    ->where('product_id', $product->id)
                    ->where('year', $histMonth->year)
                    ->where('month', $histMonth->month)
                    ->sum('quantity_sold');
            }

            $liveDays = max(1, $earliestLive->diffInDays($refDate));
            if ($liveSales > 0) {
                // Penjualan kasir live dinormalisasi ke bulanan
                $avgBln = round(($liveSales / $liveDays) * 30, 1);
                $isSalesFromHistory = false;
            } elseif ($histSales > 0) {
                // Di masa awal migrasi produk belum terjual di kasir baru, gunakan histori bulan sebelumnya
                $avgBln = round($histSales, 1);
                $isSalesFromHistory = true;
            } else {
                $avgBln = 0.0;
                $isSalesFromHistory = false;
            }
            $avgMinggu = round($avgBln / 4, 1);
        } else {
            // Kasus C: Cabang belum ada transaksi kasir sama sekali di SM Inventory. 100% dari histori Excel!
            $latestHist = ProductMonthlySalesHistory::where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->first();
            if ($latestHist && (float)$latestHist->quantity_sold > 0) {
                $avgBln = round((float)$latestHist->quantity_sold, 1);
                $avgMinggu = round($avgBln / 4, 1);
                $isSalesFromHistory = true;
            }
        }

        $minQty = (float)($stockModel->min_qty ?? 0);
        $maxQty = (float)($stockModel->max_qty ?? 0);

        // Ambil riwayat retur terakhir dalam 90 hari
        $lastReturn = $this->getProductReturnInfo($product->id, $branchId);

        // 2. Hitung Penjualan 90 Hari (Continuous Time-Series Stitching)
        $sales90d = 0.0;
        if ($earliestLive && $earliestLive->lte($ninetyDaysAgo)) {
            // Cabang sudah aktif >= 90 hari di SM Inventory. 100% data live kasir!
            $sales90d = (float)TransactionItem::where('product_id', $product->id)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->whereBetween('created_at', [$ninetyDaysAgo, $refDate]))
                ->sum('quantity');
        } elseif ($earliestLive) {
            // Cabang aktif < 90 hari. Sambungkan transaksi live + bulan sebelum migrasi yang masuk rentang 90 hari!
            $live90d = (float)TransactionItem::where('product_id', $product->id)
                ->whereHas('transaction', fn($q) => $q->where('branch_id', $branchId)->whereBetween('created_at', [$earliestLive, $refDate]))
                ->sum('quantity');

            $preLiveCutoff = $earliestLive->copy()->startOfMonth();
            $hist90d = 0.0;
            for ($i = 1; $i <= 3; $i++) {
                $dt = $refDate->copy()->subMonths($i);
                if ($dt->lt($preLiveCutoff)) {
                    $hist90d += (float)ProductMonthlySalesHistory::where('branch_id', $branchId)
                        ->where('product_id', $product->id)
                        ->where('year', $dt->year)
                        ->where('month', $dt->month)
                        ->sum('quantity_sold');
                }
            }
            $sales90d = round($live90d + $hist90d, 1);
        } else {
            // Cabang belum ada transaksi kasir. Ambil 3 bulan terakhir dari histori Excel!
            $sales90d = (float)ProductMonthlySalesHistory::where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->take(3)
                ->sum('quantity_sold');
        }

        // Deteksi apakah stok habis karena retur:
        // Syarat Discontinue:
        // 1. Stok <= 0
        // 2. Ada retur dalam 90 hari terakhir
        // 3. Belum ada penerimaan barang baru sejak retur
        // 4. Penjualan 30 hari = 0 DAN penjualan 90 hari < 10 pcs (barang lesu yang sisa stoknya diretur, bukan barang aktif yang kebetulan retur barang rusak)
        $isEmptyDueToReturn = false;
        if (($stock <= 0) && $lastReturn !== null && $avgBln <= 0 && $sales90d < 10) {
            $hasNewReceipt = GoodsReceiptItem::where('product_id', $product->id)
                ->whereHas('goodsReceipt', function ($q) use ($branchId, $lastReturn) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                    $q->where('receipt_date', '>', $lastReturn['date'])
                      ->whereIn('status', ['COMPLETED', 'completed', 'APPROVED', 'approved', 'received']);
                })
                ->exists();
            $isEmptyDueToReturn = !$hasNewReceipt;
        }

        // Deteksi Pasokan Macet (OOS): Stok <= 0 dan Sales 30 HR = 0, tetapi ada penjualan aktif signifikan di 90 hari terakhir (>= 10 pcs)
        $isOosWithHistory = false;
        $avgSalesActive = 0;
        if (!$isEmptyDueToReturn && $avgBln <= 0 && $stock <= 0) {
            if ($sales90d >= 10) {
                $isOosWithHistory = true;
                $avgSalesActive = round($sales90d / 3, 1);
            }
        }

        // Deteksi Slow Moving Alert:
        // Produk HANYA dikategorikan Slow Moving jika penjualan 90 harinya terbukti sangat rendah (< 10 pcs / < 1 pcs per minggu):
        // 1. Sisa stok masih ada (> 0) namun penjualan 30 hari sangat rendah (<= 2 pcs) DAN 90 hari < 10 pcs
        // 2. ATAU Stok habis (<= 0) dan penjualan 90 hari < 10 pcs
        $isSlowMovingAlert = false;
        if (!$isEmptyDueToReturn && !$isOosWithHistory && ($qtySaran > 0 || ($minQty > 0 && $stock < $minQty))) {
            if ($sales90d < 10) {
                if ($stock > 0 && $avgBln <= 2) {
                    $isSlowMovingAlert = true;
                } elseif ($stock <= 0 && $avgBln <= 0) {
                    $isSlowMovingAlert = true;
                }
            }
        }

        $d1 = $customDiscounts['discount_1'] ?? 0;
        $d2 = $customDiscounts['discount_2'] ?? 0;
        $d3 = $customDiscounts['discount_3'] ?? 0;

        $subtotal = $customSubtotal;
        if ($subtotal === null) {
            $baseTotal = $qty * $finalCost;
            $t1 = $baseTotal - ($baseTotal * ($d1 / 100));
            $t2 = $t1 - ($t1 * ($d2 / 100));
            $t3 = $t2 - ($t2 * ($d3 / 100));
            $subtotal = round($t3, 2);
        }

        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'max_order' => 0,
            'avg_bln' => $avgBln,
            'avg_minggu' => $avgMinggu,
            'sales_90d' => $sales90d,
            'is_oos_with_history' => $isOosWithHistory,
            'avg_sales_active' => $avgSalesActive,
            'is_sales_from_history' => $isSalesFromHistory,
            'stock' => $stock ?? 0,
            'min_qty' => $minQty,
            'max_qty' => $maxQty,
            'qty_saran' => (float)$qtySaran,
            'qty' => (float)$qty,
            'unit_cost' => $finalCost,
            'discount_1' => (float)$d1,
            'discount_2' => (float)$d2,
            'discount_3' => (float)$d3,
            'subtotal' => (float)$subtotal,
            'last_return' => $lastReturn,
            'is_empty_due_to_return' => $isEmptyDueToReturn,
            'is_slow_moving_alert' => $isSlowMovingAlert,
            'promo_note' => $promoNote,
        ];
    }

    public function discontinueBranchProduct($productId)
    {
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih lokasi cabang terlebih dahulu!')->warning()->send();
            return;
        }

        $stock = Stock::where('product_id', $productId)
            ->where('branch_id', $this->branch_id)
            ->first();

        if ($stock) {
            $stock->is_active = false;
            $stock->save();

            // Hapus produk dari keranjang PO saat ini
            $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $productId);
            if ($existingIndex !== false) {
                unset($this->cart[$existingIndex]);
                $this->cart = array_values($this->cart);
                $this->calculateTotals();
            }

            Notification::make()
                ->title('Produk berhasil di-discontinue untuk cabang ini.')
                ->success()
                ->send();
        } else {
            Notification::make()->title('Data stok cabang tidak ditemukan!')->warning()->send();
        }
    }

    public function openPromoModal()
    {
        if (empty($this->supplier_id)) {
            Notification::make()->title('Pilih Supplier terlebih dahulu sebelum memuat promo!')->warning()->send();
            return;
        }

        $this->showPromoModal = true;
        $this->promoErrorMessage = '';
    }

    public function closePromoModal()
    {
        $this->showPromoModal = false;
        $this->promoErrorMessage = '';
    }

    public function updatedPromoFile()
    {
        $this->promoErrorMessage = '';
        if (!$this->promoFile) return;

        try {
            $service = app(PromoParserService::class);
            $path = $this->promoFile->getRealPath();
            $this->excelSheets = $service->getSheetNames($path);
            $this->selectedSheet = $this->excelSheets[0] ?? '';
            $this->detectHeadersForSelectedSheet();
        } catch (\Throwable $e) {
            $this->promoErrorMessage = 'Gagal membaca file Excel: ' . $e->getMessage();
        }
    }

    public function updatedSelectedSheet()
    {
        $this->detectHeadersForSelectedSheet();
    }

    public function detectHeadersForSelectedSheet()
    {
        if (!$this->promoFile) return;

        try {
            $service = app(PromoParserService::class);
            $path = $this->promoFile->getRealPath();
            $detected = $service->detectHeaders($path, $this->selectedSheet);

            $this->excelColumns = $detected['columns'] ?? [];
            $this->selectedBarcodeCol = $detected['barcode_col'] ?? 'A';
            $this->selectedPromoCol = $detected['promo_col'] ?? 'D';
            $this->selectedNameCol = $detected['name_col'] ?? 'C';
            $this->startRow = ($detected['header_row'] ?? 1) + 1;
        } catch (\Throwable $e) {
            $this->promoErrorMessage = 'Gagal mendeteksi header sheet: ' . $e->getMessage();
        }
    }

    public function processPromoData()
    {
        $this->promoErrorMessage = '';
        $service = app(PromoParserService::class);

        try {
            $parsedRaw = [];
            if ($this->promoSource === 'excel') {
                if (!$this->promoFile) {
                    $this->promoErrorMessage = 'Silakan pilih/upload file Excel terlebih dahulu.';
                    return;
                }
                $path = $this->promoFile->getRealPath();
                $parsedRaw = $service->parseExcel(
                    $path,
                    $this->selectedSheet,
                    $this->selectedBarcodeCol,
                    $this->selectedPromoCol,
                    $this->selectedNameCol,
                    (int)$this->startRow
                );
            } else {
                if (empty(trim($this->promoText))) {
                    $this->promoErrorMessage = 'Silakan tempel (paste) teks WhatsApp terlebih dahulu.';
                    return;
                }
                $parsedRaw = $service->parseWhatsAppText($this->promoText);
            }

            if (empty($parsedRaw)) {
                $this->promoErrorMessage = 'Tidak ada data barcode dan promo yang berhasil dibaca. Pastikan format kolom/teks sesuai.';
                return;
            }

            // Hubungkan dengan data inventaris toko
            $this->parsedPromoItems = $service->matchWithInventory($parsedRaw, $this->branch_id, $this->cart);

            $matchedCount = collect($this->parsedPromoItems)->where('is_matched', true)->count();
            Notification::make()
                ->title("Berhasil membaca " . count($this->parsedPromoItems) . " promo ({$matchedCount} cocok dengan master barang).")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            $this->promoErrorMessage = 'Terjadi kesalahan saat memproses promo: ' . $e->getMessage();
        }
    }

    public function togglePromoItemSelection($index)
    {
        if (isset($this->parsedPromoItems[$index])) {
            $this->parsedPromoItems[$index]['selected'] = !$this->parsedPromoItems[$index]['selected'];
        }
    }

    public function setAllPromoSelection($state)
    {
        foreach ($this->parsedPromoItems as &$item) {
            if ($item['is_matched']) {
                $item['selected'] = $state;
            }
        }
    }

    public function selectOnlyNeedOrderPromo()
    {
        foreach ($this->parsedPromoItems as &$item) {
            if ($item['is_matched']) {
                $item['selected'] = ($item['recommendation'] === 'need_order' || $item['in_cart']);
            }
        }
    }

    public function applyPromoToCart()
    {
        if (empty($this->parsedPromoItems)) return;

        $updatedCartCount = 0;
        $addedCartCount = 0;

        foreach ($this->parsedPromoItems as $promoItem) {
            if (!$promoItem['is_matched'] || empty($promoItem['selected'])) {
                continue;
            }

            // Cari jika sudah ada di keranjang
            $cartIndex = collect($this->cart)->search(fn($ci) => $ci['product_id'] == $promoItem['product_id']);

            if ($cartIndex !== false) {
                // Perbarui diskon & promo note
                if ((float)$promoItem['disc_percent'] > 0) {
                    $this->cart[$cartIndex]['discount_1'] = (float)$promoItem['disc_percent'];
                }
                if (!empty($promoItem['promo_note'])) {
                    $this->cart[$cartIndex]['promo_note'] = $promoItem['promo_note'];
                }
                $this->recalculateRow($cartIndex);
                $updatedCartCount++;
            } elseif (!$this->updateExistingCartOnly) {
                // Tambahkan barang baru ke keranjang
                $product = Product::find($promoItem['product_id']);
                if ($product) {
                    $qtyToOrder = $promoItem['qty_saran'] > 0 ? (float)$promoItem['qty_saran'] : 0;
                    $discounts = [];
                    if ((float)$promoItem['disc_percent'] > 0) {
                        $discounts['discount_1'] = (float)$promoItem['disc_percent'];
                    }

                    $this->cart[] = $this->resolveCartItemData(
                        $product,
                        $this->branch_id,
                        $qtyToOrder,
                        (float)$promoItem['qty_saran'],
                        null,
                        $discounts,
                        null,
                        $promoItem['promo_note']
                    );
                    $this->recalculateRow(count($this->cart) - 1);
                    $addedCartCount++;
                }
            }
        }

        $this->calculateTotals();
        $this->showPromoModal = false;

        $msg = [];
        if ($updatedCartCount > 0) $msg[] = "{$updatedCartCount} diskon keranjang diperbarui";
        if ($addedCartCount > 0) $msg[] = "{$addedCartCount} barang promo ditambahkan";

        if (empty($msg)) {
            Notification::make()->title('Tidak ada item yang diterapkan ke keranjang.')->info()->send();
        } else {
            Notification::make()->title(implode(', ', $msg) . '.')->success()->send();
        }
    }

    public function render()
    {
        return view('livewire.purchase-order-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::where('is_active', true)
                ->when($this->supplier_id, fn($q) => $q->orWhere('id', $this->supplier_id))
                ->orderBy('name', 'asc')
                ->get(),
            'topHistory' => PurchaseOrder::where('status', 'DRAFT')->latest()->limit(10)->get()
        ]);
    }
}


