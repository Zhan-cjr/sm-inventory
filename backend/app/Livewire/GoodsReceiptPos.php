<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
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
    use WithFileUploads;
    use Traits\HasPosDraft;


    public $receipt_number;
    public $receipt_date;
    public $due_date;
    public $faktur_supplier;
    public $faktur_image = [];
    public $existing_faktur_image = [];
    public $branch_id;
    public $notes;
    public $supplier_id;
    public $supplier_division_id;
    public $payment_method = 'tempo';
    public $purchase_order_id;
    public $include_tax = true;
    public $tax_type = 'include'; // 'include', 'exclude', 'non_tax'
    public $tax_amount = 0;
    public $dpp_amount = 0;
    public $cetak_nota = false;

    public $visibleColumns = ['barcode', 'name', 'qty_ordered', 'qty_received', 'unit_price', 'harga_jual_1', 'margin_gol_1', 'discount_1', 'discount_2', 'discount_3', 'subtotal'];

    public $searchQuery = '';
    public $cart = [];

    // Summary
    public $totalQty = 0;
    public $totalLines = 0;
    public $subtotal = 0;
    public $totalItemDiscount = 0;
    public $discount_subtotal = 0;
    public $discount_subtotal_type = 'nominal'; // 'percent' or 'nominal'
    public $grandTotal = 0;

    public $searchResults = [];
    public $goodsReceipt;

    public $only_latest_po = false;
    public $gr_requires_po = false;
    public $taxRate = 11;

    public function hasPoBypassAuthorization(): bool
    {
        $user = auth()->user() ?? \Filament\Facades\Filament::auth()->user();
        return $user ? $user->hasCustomAuthorization('BYPASS_GR_PO_REQUIRED') : false;
    }

    public function mount($goodsReceipt = null)
    {
        $this->taxRate = (float) \App\Services\RetailIntelligenceService::getActiveTaxRate();

        if ($goodsReceipt) {
            $this->goodsReceipt = $goodsReceipt;
            $this->receipt_number = $goodsReceipt->receipt_number;
            $this->receipt_date = $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('Y-m-d') : null;
            $this->due_date = $goodsReceipt->due_date ? $goodsReceipt->due_date->format('Y-m-d') : null;
            $this->faktur_supplier = $goodsReceipt->faktur_supplier;
            $this->branch_id = $goodsReceipt->branch_id;
            $this->notes = $goodsReceipt->notes;
            $this->supplier_id = $goodsReceipt->supplier_id;
            $this->supplier_division_id = $goodsReceipt->supplier_division_id;
            $this->payment_method = $goodsReceipt->payment_method ?? 'tempo';
            $this->purchase_order_id = $goodsReceipt->purchase_order_id;
            $this->tax_type = $goodsReceipt->tax_type ?? ($goodsReceipt->include_tax ? 'include' : 'non_tax');
            $this->include_tax = ($this->tax_type !== 'non_tax');
            $this->tax_amount = $goodsReceipt->tax_amount;
            $this->discount_subtotal = (float) ($goodsReceipt->discount_subtotal ?? 0);
            $this->discount_subtotal_type = $goodsReceipt->discount_subtotal_type ?? 'nominal';
            $this->existing_faktur_image = is_array($goodsReceipt->faktur_image) ? $goodsReceipt->faktur_image : ($goodsReceipt->faktur_image ? [$goodsReceipt->faktur_image] : []);
            $this->faktur_image = [];

            $taxMultiplier = 1 + ($this->taxRate / 100);

            // Cek apakah draft ini berasal dari Warehouse Check / PO lama yang unit_price nya terisi harga include PPN (cost_price_tax)
            $isDraftFromCheck = ($goodsReceipt->status === 'DRAFT' || !empty($goodsReceipt->warehouse_check_id));
            $hasTaxable = false;
            foreach ($goodsReceipt->items as $item) {
                if ($item->product?->is_taxable ?? true) {
                    $hasTaxable = true;
                    break;
                }
            }
            if ($isDraftFromCheck && !$this->include_tax && $hasTaxable) {
                $this->include_tax = true;
            }

            foreach ($goodsReceipt->items as $item) {
                $stock = null;
                if ($this->branch_id) {
                    $stock = Stock::where('product_id', $item->product_id)->where('branch_id', $this->branch_id)->first();
                }

                $isTaxable = (bool) ($item->product?->is_taxable ?? true);
                $rawPrice = (float) $item->unit_price;

                if ($isDraftFromCheck && $isTaxable) {
                    $stockTax = $stock ? (float) $stock->cost_price_tax : 0;
                    $prodTax = $item->product ? (float) $item->product->cost_price_tax : 0;

                    // Jika rawPrice sama dengan cost_price_tax (harga include PPN) atau draft GR tersimpan tanpa include_tax
                    if (($stockTax > 0 && abs($stockTax - $rawPrice) < 1.0) || ($prodTax > 0 && abs($prodTax - $rawPrice) < 1.0) || !$goodsReceipt->include_tax) {
                        $unitPriceTax = $rawPrice;
                        $unitPrice = round($rawPrice / $taxMultiplier, 4);
                    } else {
                        $unitPrice = $rawPrice;
                        $unitPriceTax = round($rawPrice * $taxMultiplier, 2);
                    }
                } elseif ($isTaxable) {
                    $unitPrice = $rawPrice;
                    $unitPriceTax = round($rawPrice * $taxMultiplier, 2);
                } else {
                    $unitPrice = $rawPrice;
                    $unitPriceTax = $rawPrice;
                }

                $this->cart[] = [
                    'product_id' => $item->product_id,
                    'sku' => $item->product ? $item->product->sku : '',
                    'barcode' => $item->product ? $item->product->barcode : '',
                    'name' => $item->product ? $item->product->name : '',
                    'is_taxable' => $isTaxable,
                    'qty_ordered' => $item->quantity_ordered,
                    'qty_received' => $item->quantity_received,
                    'unit_price' => $unitPrice,
                    'unit_price_tax' => $unitPriceTax,
                    'unit_price_net_tax' => $unitPriceTax,
                    'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($item->product->harga_jual_1 ?? 0),
                    'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($item->product->margin_gol_1 ?? 0),
                    'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($item->product->harga_jual_2 ?? 0),
                    'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($item->product->margin_gol_2 ?? 0),
                    'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($item->product->harga_jual_3 ?? 0),
                    'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($item->product->margin_gol_3 ?? 0),
                    'discount_1' => $item->discount_1,
                    'discount_1_type' => $item->discount_1_type ?? 'percent',
                    'discount_2' => $item->discount_2,
                    'discount_2_type' => $item->discount_2_type ?? 'percent',
                    'discount_3' => $item->discount_3,
                    'discount_3_type' => $item->discount_3_type ?? 'percent',
                    'subtotal' => 0,
                ];
                $this->recalculateRow(count($this->cart) - 1, false);
                $this->syncRowMargins(count($this->cart) - 1);
            }
        } else {
            $this->receipt_number = 'GR-' . date('YmdHis');
            $this->receipt_date = date('Y-m-d');
            $this->due_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;

            // Load draft if not editing existing GR
            $this->loadDraft();
        }

        if ($this->supplier_id) {
            $supplier = Supplier::find($this->supplier_id);
            $this->gr_requires_po = (bool) ($supplier?->gr_requires_po);
            $this->loadAvailablePurchaseOrders();
        }

        $this->calculateTotals();
    }

    public function loadAvailablePurchaseOrders()
    {
        // Handled via computed property getPurchaseOrdersProperty()
    }

    public function getPurchaseOrdersProperty()
    {
        if (!$this->supplier_id) {
            return collect();
        }

        $query = PurchaseOrder::where(function ($q) {
            $q->whereIn('status', ['APPROVED', 'approved', 'PARTIALLY_RECEIVED', 'partially_received'])
              ->whereHas('warehouseChecks', function ($qc) {
                  $qc->whereIn('status', ['approved', 'partially_processed']);
              })
              ->whereHas('items', function ($query) {
                  $query->whereColumn('quantity_received', '<', 'quantity_ordered');
              })
              ->where(function ($sub) {
                  $sub->whereNull('expired_date')
                      ->orWhere('expired_date', '>=', now()->toDateString());
              })
              ->where('supplier_id', $this->supplier_id);
        });

        if ($this->purchase_order_id) {
            $query->orWhere('id', $this->purchase_order_id);
        } else if ($this->only_latest_po) {
            $query->latest('created_at')->limit(1);
        }

        return $query->get();
    }

    public function dehydrate()
    {
        $this->saveDraft();
    }

    public function updatedSupplierId($value)
    {
        $this->supplier_division_id = null;
        $this->recalculateDueDate();
        if ($value) {
            $supplier = Supplier::find($value);
            $this->gr_requires_po = (bool) ($supplier?->gr_requires_po);
        } else {
            $this->gr_requires_po = false;
        }
        $this->loadAvailablePurchaseOrders();
    }

    public function updatedReceiptDate($value)
    {
        $this->recalculateDueDate();
    }

    private function recalculateDueDate()
    {
        if ($this->supplier_id && $this->receipt_date) {
            $supplier = Supplier::find($this->supplier_id);
            if ($supplier) {
                $this->due_date = \Carbon\Carbon::parse($this->receipt_date)->addDays($supplier->default_due_days)->format('Y-m-d');
            }
        }
    }

    public function updatedPurchaseOrderId($value)
    {
        if ($value) {
            $po = PurchaseOrder::with('items.product')->find($value);
            if ($po) {
                $this->supplier_id = $po->supplier_id;
                $this->supplier_division_id = $po->supplier_division_id;
                $this->recalculateDueDate();
                
                $warehouseCheck = \App\Models\WarehouseCheck::where('purchase_order_id', $po->id)
                    ->whereIn('status', ['approved', 'partially_processed', 'processed'])
                    ->with('items.product')
                    ->latest()
                    ->first();

                $existingGrIds = \App\Models\GoodsReceipt::where('status', '!=', 'CANCELLED')
                    ->where(function ($q) use ($warehouseCheck, $po) {
                        if ($warehouseCheck) {
                            $q->where('warehouse_check_id', $warehouseCheck->id);
                        }
                        $q->orWhere('purchase_order_id', $po->id);
                    })
                    ->when($this->goodsReceipt, fn($q) => $q->where('id', '!=', $this->goodsReceipt->id))
                    ->pluck('id');

                $this->cart = [];

                if ($warehouseCheck && $warehouseCheck->items->count() > 0) {
                    foreach ($warehouseCheck->items as $checkItem) {
                        if (!$checkItem->product || $checkItem->qty_scanned <= 0) continue;

                        $alreadyReceived = \App\Models\GoodsReceiptItem::whereIn('goods_receipt_id', $existingGrIds)
                            ->where('product_id', $checkItem->product_id)
                            ->sum('quantity_received');

                        $remainingQty = max(0, (float) $checkItem->qty_scanned - (float) $alreadyReceived);
                        if ($existingGrIds->count() > 0 && $remainingQty <= 0) {
                            continue;
                        }

                        $qtyToDefault = ($existingGrIds->count() > 0) ? $remainingQty : (float) $checkItem->qty_scanned;

                        $stock = null;
                        if ($this->branch_id) {
                            $stock = Stock::where('product_id', $checkItem->product_id)->where('branch_id', $this->branch_id)->first();
                        }

                        $isTaxable = (bool) ($checkItem->product->is_taxable ?? true);
                        $taxMultiplier = 1 + ($this->taxRate / 100);

                        $poItem = $po->items->firstWhere('product_id', $checkItem->product_id);
                        $poCost = $poItem ? (float) ($poItem->unit_cost ?? 0) : 0;
                        $disc1 = $poItem ? (float) ($poItem->discount_1 ?? 0) : 0;
                        $disc2 = $poItem ? (float) ($poItem->discount_2 ?? 0) : 0;
                        $disc3 = $poItem ? (float) ($poItem->discount_3 ?? 0) : 0;

                        if ($isTaxable) {
                            // PO unit_cost di sistem ini selalu menyimpan harga include PPN (cost_price_tax)
                            $unitPriceTax = $poCost;
                            if ($stock && $stock->cost_price > 0 && abs(round($stock->cost_price * $taxMultiplier, 2) - $poCost) < 1.0) {
                                $unitPrice = (float) $stock->cost_price;
                            } else {
                                $unitPrice = round($poCost / $taxMultiplier, 4);
                            }
                        } else {
                            $unitPrice = $poCost;
                            $unitPriceTax = $poCost;
                        }

                        $this->cart[] = [
                            'product_id' => $checkItem->product_id,
                            'sku' => $checkItem->product->sku,
                            'barcode' => $checkItem->product->barcode,
                            'name' => $checkItem->product->name,
                            'is_taxable' => $isTaxable,
                            'qty_ordered' => (float) $checkItem->qty_po,
                            'qty_received' => (float) $qtyToDefault,
                            'unit_price' => $unitPrice,
                            'unit_price_tax' => $unitPriceTax,
                            'unit_price_net_tax' => $unitPriceTax,
                            'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($checkItem->product->harga_jual_1 ?? 0),
                            'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($checkItem->product->margin_gol_1 ?? 0),
                            'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($checkItem->product->harga_jual_2 ?? 0),
                            'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($checkItem->product->margin_gol_2 ?? 0),
                            'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($checkItem->product->harga_jual_3 ?? 0),
                            'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($checkItem->product->margin_gol_3 ?? 0),
                            'discount_1' => $disc1,
                            'discount_1_type' => 'percent',
                            'discount_2' => $disc2,
                            'discount_2_type' => 'percent',
                            'discount_3' => $disc3,
                            'discount_3_type' => 'percent',
                            'subtotal' => 0,
                        ];
                        $this->recalculateRow(count($this->cart) - 1, false);
                        $this->syncRowMargins(count($this->cart) - 1);
                    }
                } else {
                    foreach ($po->items as $item) {
                        if (!$item->product) continue;

                        $alreadyReceived = \App\Models\GoodsReceiptItem::whereIn('goods_receipt_id', $existingGrIds)
                            ->where('product_id', $item->product_id)
                            ->sum('quantity_received');

                        $remainingQty = max(0, (float) $item->quantity_ordered - (float) $alreadyReceived);
                        if ($existingGrIds->count() > 0 && $remainingQty <= 0) {
                            continue;
                        }

                        $qtyToDefault = ($existingGrIds->count() > 0) ? $remainingQty : (float) $item->quantity_ordered;

                        $stock = null;
                        if ($this->branch_id) {
                            $stock = Stock::where('product_id', $item->product_id)->where('branch_id', $this->branch_id)->first();
                        }

                        $isTaxable = (bool) ($item->product?->is_taxable ?? true);
                        $taxMultiplier = 1 + ($this->taxRate / 100);
                        $poCost = (float) ($item->unit_cost ?? 0);

                        if ($isTaxable) {
                            // PO unit_cost di sistem ini selalu menyimpan harga include PPN (cost_price_tax)
                            $unitPriceTax = $poCost;
                            $unitPrice = round($poCost / $taxMultiplier, 4);
                        } else {
                            $unitPrice = $poCost;
                            $unitPriceTax = $poCost;
                        }

                        $this->cart[] = [
                            'product_id' => $item->product_id,
                            'sku' => $item->product->sku,
                            'barcode' => $item->product->barcode,
                            'name' => $item->product->name,
                            'is_taxable' => $isTaxable,
                            'qty_ordered' => (float) $item->quantity_ordered,
                            'qty_received' => (float) $qtyToDefault,
                            'unit_price' => $unitPrice,
                            'unit_price_tax' => $unitPriceTax,
                            'unit_price_net_tax' => $unitPriceTax,
                            'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($item->product->harga_jual_1 ?? 0),
                            'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($item->product->margin_gol_1 ?? 0),
                            'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($item->product->harga_jual_2 ?? 0),
                            'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($item->product->margin_gol_2 ?? 0),
                            'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($item->product->harga_jual_3 ?? 0),
                            'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($item->product->margin_gol_3 ?? 0),
                            'discount_1' => (float) ($item->discount_1 ?? 0),
                            'discount_1_type' => 'percent',
                            'discount_2' => (float) ($item->discount_2 ?? 0),
                            'discount_2_type' => 'percent',
                            'discount_3' => (float) ($item->discount_3 ?? 0),
                            'discount_3_type' => 'percent',
                            'subtotal' => 0,
                        ];
                        $this->recalculateRow(count($this->cart) - 1, false);
                        $this->syncRowMargins(count($this->cart) - 1);
                    }
                }

                $hasTaxableInCart = collect($this->cart)->contains(fn($ci) => (bool) ($ci['is_taxable'] ?? true));
                $this->include_tax = $hasTaxableInCart;
                $this->calculateTotals();
            }
        }
    }

    public function updatedBranchId($value)
    {
        $this->searchResults = [];
        $this->searchQuery = '';
        
        if (!empty($this->cart)) {
            foreach ($this->cart as $index => $item) {
                $stock = null;
                if ($value) {
                    $stock = Stock::where('product_id', $item['product_id'])->where('branch_id', $value)->first();
                }
                $product = Product::find($item['product_id']);
                
                $this->cart[$index]['harga_jual_1'] = ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($product?->harga_jual_1 ?? 0);
                $this->cart[$index]['margin_gol_1'] = ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($product?->margin_gol_1 ?? 0);
                $this->cart[$index]['harga_jual_2'] = ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($product?->harga_jual_2 ?? 0);
                $this->cart[$index]['margin_gol_2'] = ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($product?->margin_gol_2 ?? 0);
                $this->cart[$index]['harga_jual_3'] = ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($product?->harga_jual_3 ?? 0);
                $this->cart[$index]['margin_gol_3'] = ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($product?->margin_gol_3 ?? 0);
                $this->syncRowMargins($index);
            }
        }
    }

    public function updatedSearchQuery($value)
    {
        if (empty($this->branch_id) || empty($this->supplier_id)) {
            $this->searchResults = [];
            return;
        }

        if ($this->gr_requires_po && empty($this->purchase_order_id)) {
            if (!$this->hasPoBypassAuthorization()) {
                $this->searchResults = [];
                return;
            }
        }

        if (!empty($this->purchase_order_id) || (!empty($this->goodsReceipt) && !empty($this->goodsReceipt->warehouse_check_id))) {
            $this->searchResults = [];
            return;
        }

        $value = trim((string) $value);
        if (strlen($value) >= 2) {
            // 1. Prioritaskan exact match barcode/SKU di database yang terdaftar di cabang ini
            $exactMatches = Product::query()
                ->select(['id', 'sku', 'barcode', 'name', 'cost_price', 'is_taxable'])
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
                ->select(['id', 'sku', 'barcode', 'name', 'cost_price', 'is_taxable'])
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
        } else {
            $this->searchResults = [];
        }
    }

    public function selectProduct($productId)
    {
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu.')->warning()->send();
            return;
        }

        if (!empty($this->purchase_order_id)) {
            if (!$this->hasPoBypassAuthorization()) {
                Notification::make()->title('Tidak dapat menambah barang baru saat menggunakan PO.')->warning()->send();
                return;
            }
        }

        if (!empty($this->goodsReceipt) && !empty($this->goodsReceipt->warehouse_check_id)) {
            if (!$this->hasPoBypassAuthorization()) {
                Notification::make()->title('Tidak dapat menambah barang baru pada penerimaan dari Pengecekan Gudang.')->warning()->send();
                return;
            }
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
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu.')->warning()->send();
            return;
        }

        if (empty($this->supplier_id)) {
            Notification::make()->title('Pilih Pemasok terlebih dahulu.')->warning()->send();
            return;
        }

        if ($this->gr_requires_po && empty($this->purchase_order_id)) {
            if (!$this->hasPoBypassAuthorization()) {
                Notification::make()->title('Penerimaan barang wajib dengan PO untuk Pemasok ini.')->warning()->send();
                return;
            }
        }

        if (!empty($this->purchase_order_id)) {
            if (!$this->hasPoBypassAuthorization()) {
                Notification::make()->title('Tidak dapat menambah barang baru saat menggunakan PO.')->warning()->send();
                return;
            }
        }

        if (!empty($this->goodsReceipt) && !empty($this->goodsReceipt->warehouse_check_id)) {
            if (!$this->hasPoBypassAuthorization()) {
                Notification::make()->title('Tidak dapat menambah barang baru pada penerimaan dari Pengecekan Gudang.')->warning()->send();
                return;
            }
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
            $this->cart[$existingIndex]['qty_received']++;
            $this->recalculateRow($existingIndex);
            $this->dispatch('item-added', index: $existingIndex);
        } else {
            $stock = null;
            if ($this->branch_id) {
                $stock = Stock::where('product_id', $product->id)->where('branch_id', $this->branch_id)->first();
            }

            $isTaxable = (bool) ($product->is_taxable ?? true);
            $taxMultiplier = 1 + ($this->taxRate / 100);

            $costPrice = ($stock && $stock->cost_price > 0) ? (float) $stock->cost_price : (float) ($product->cost_price ?? 0);
            $costPriceTax = ($stock && $stock->cost_price_tax > 0) ? (float) $stock->cost_price_tax : ($isTaxable ? round($costPrice * $taxMultiplier, 2) : $costPrice);

            if (!$isTaxable) {
                $costPriceTax = $costPrice;
            }

            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'is_taxable' => $isTaxable,
                'qty_ordered' => 0,
                'qty_received' => 1,
                'unit_price' => $costPrice,
                'unit_price_tax' => $costPriceTax,
                'unit_price_net_tax' => $costPriceTax,
                'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($product->harga_jual_1 ?? 0),
                'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($product->margin_gol_1 ?? 0),
                'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($product->harga_jual_2 ?? 0),
                'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($product->margin_gol_2 ?? 0),
                'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($product->harga_jual_3 ?? 0),
                'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($product->margin_gol_3 ?? 0),
                'discount_1' => 0,
                'discount_1_type' => 'percent',
                'discount_2' => 0,
                'discount_2_type' => 'percent',
                'discount_3' => 0,
                'discount_3_type' => 'percent',
                'subtotal' => $costPrice
            ];
            $this->syncRowMargins(count($this->cart) - 1);
        }

        $this->calculateTotals();
    }

    public function getNetCostPriceTax(int $index): float
    {
        if (!isset($this->cart[$index])) {
            return 0.0;
        }

        $item = $this->cart[$index];
        $qty = (float) ($item['qty_received'] ?? 0);
        $subtotal = (float) ($item['subtotal'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $netPrice = $qty > 0 ? ($subtotal / $qty) : $unitPrice;

        $isTaxable = (bool) ($item['is_taxable'] ?? true);
        $taxMultiplier = 1 + ($this->taxRate / 100);

        $costNetTax = ($this->include_tax && $isTaxable) ? round($netPrice * $taxMultiplier, 2) : round($netPrice, 2);

        if ($costNetTax <= 0) {
            $costNetTax = (float) ($item['unit_price_tax'] ?? $item['unit_price'] ?? 0);
        }

        return $costNetTax;
    }

    public function syncRowMargins(int $index, ?string $changedField = null, $changedValue = null)
    {
        if (!isset($this->cart[$index])) {
            return;
        }

        // Basis margin harga jual: Modal Netto (+PPN) setelah diskon faktur
        $costBasis = $this->getNetCostPriceTax($index);
        $this->cart[$index]['unit_price_net_tax'] = $costBasis;

        if (in_array($changedField, ['margin_gol_1', 'margin_gol_2', 'margin_gol_3'])) {
            $gol = substr($changedField, -1);
            $margin = (float) $changedValue;
            $this->cart[$index][$changedField] = $margin;
            if ($costBasis > 0) {
                $this->cart[$index]["harga_jual_{$gol}"] = round($costBasis * (1 + ($margin / 100)), 2);
            }
        } elseif (in_array($changedField, ['harga_jual_1', 'harga_jual_2', 'harga_jual_3'])) {
            $gol = substr($changedField, -1);
            $sellingPrice = (float) $changedValue;
            $this->cart[$index][$changedField] = $sellingPrice;
            if ($sellingPrice > 0 && $costBasis > 0) {
                $this->cart[$index]["margin_gol_{$gol}"] = round((($sellingPrice - $costBasis) / $costBasis) * 100, 2);
            } else {
                $this->cart[$index]["margin_gol_{$gol}"] = 0;
            }
        } else {
            // Sinkronisasi otomatis untuk semua golongan berdasarkan harga_jual yang ada terhadap Modal Netto (+PPN)
            foreach ([1, 2, 3] as $i) {
                $sellingPrice = (float) ($this->cart[$index]["harga_jual_{$i}"] ?? 0);
                if ($sellingPrice > 0 && $costBasis > 0) {
                    $this->cart[$index]["margin_gol_{$i}"] = round((($sellingPrice - $costBasis) / $costBasis) * 100, 2);
                } elseif ($sellingPrice <= 0 && ($this->cart[$index]["margin_gol_{$i}"] ?? 0) > 0 && $costBasis > 0) {
                    $margin = (float) $this->cart[$index]["margin_gol_{$i}"];
                    $this->cart[$index]["harga_jual_{$i}"] = round($costBasis * (1 + ($margin / 100)), 2);
                } else {
                    $this->cart[$index]["margin_gol_{$i}"] = 0;
                }
            }
        }
    }

    public function updateRow($index, $field, $value)
    {
        if (!isset($this->cart[$index])) {
            return;
        }

        if ($field === 'qty_received' && (!empty($this->purchase_order_id) || (!empty($this->goodsReceipt) && !empty($this->goodsReceipt->warehouse_check_id)))) {
            if (!$this->hasPoBypassAuthorization()) {
                Notification::make()->title('Qty terima disinkronkan dari Cek Gudang / PO dan tidak dapat diubah secara manual.')->warning()->send();
                return;
            }
        }

        if ($field === 'unit_price_tax') {
            $taxVal = (float) $value;
            $isTaxable = (bool) ($this->cart[$index]['is_taxable'] ?? true);
            $taxMultiplier = 1 + ($this->taxRate / 100);
            
            $this->cart[$index]['unit_price_tax'] = $taxVal;
            $this->cart[$index]['unit_price'] = $isTaxable ? ($taxMultiplier > 0 ? round($taxVal / $taxMultiplier, 4) : $taxVal) : $taxVal;
            $this->recalculateRow($index, false);
            $this->syncRowMargins($index);
            $this->calculateTotals();
            return;
        }

        if ($field === 'unit_price') {
            $dppVal = (float) $value;
            $isTaxable = (bool) ($this->cart[$index]['is_taxable'] ?? true);
            $taxMultiplier = 1 + ($this->taxRate / 100);
            
            $this->cart[$index]['unit_price'] = $dppVal;
            $this->cart[$index]['unit_price_tax'] = $isTaxable ? round($dppVal * $taxMultiplier, 2) : $dppVal;
            $this->recalculateRow($index, false);
            $this->syncRowMargins($index);
            $this->calculateTotals();
            return;
        }

        if (in_array($field, ['discount_1', 'discount_2', 'discount_3'])) {
            $cleaned = (float) str_replace(',', '', (string) $value);
            $this->cart[$index][$field] = $cleaned;
            $this->recalculateRow($index, false);
            $this->syncRowMargins($index);
            $this->calculateTotals();
            return;
        }

        if ($field === 'subtotal') {
            $qty = (float) ($this->cart[$index]['qty_received'] ?? 0);
            $subtotal = (float) $value;
            if ($qty > 0) {
                $d1 = ((float) ($this->cart[$index]['discount_1'] ?? 0)) / 100;
                $d2 = ((float) ($this->cart[$index]['discount_2'] ?? 0)) / 100;
                $d3 = ((float) ($this->cart[$index]['discount_3'] ?? 0)) / 100;
                $f1 = (1 - $d1) > 0 ? (1 - $d1) : 1;
                $f2 = (1 - $d2) > 0 ? (1 - $d2) : 1;
                $f3 = (1 - $d3) > 0 ? (1 - $d3) : 1;
                $baseTotal = $subtotal / ($f1 * $f2 * $f3);
                $this->cart[$index]['unit_price'] = round($baseTotal / $qty, 4);
                $this->cart[$index]['subtotal'] = $subtotal;
                
                $isTaxable = (bool) ($this->cart[$index]['is_taxable'] ?? true);
                $taxMultiplier = 1 + ($this->taxRate / 100);
                $this->cart[$index]['unit_price_tax'] = $isTaxable ? round($this->cart[$index]['unit_price'] * $taxMultiplier, 2) : $this->cart[$index]['unit_price'];
                $this->syncRowMargins($index);
            }
            $this->calculateTotals();
            return;
        }

        if (in_array($field, ['harga_jual_1', 'harga_jual_2', 'harga_jual_3', 'margin_gol_1', 'margin_gol_2', 'margin_gol_3'])) {
            $this->syncRowMargins($index, $field, $value);
            return;
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

    public $enable_edit_total = false;

    public function removeExistingImage($index)
    {
        if (isset($this->existing_faktur_image[$index])) {
            unset($this->existing_faktur_image[$index]);
            $this->existing_faktur_image = array_values($this->existing_faktur_image);
        }
    }

    public function removeNewImage($index)
    {
        if (is_array($this->faktur_image) && isset($this->faktur_image[$index])) {
            unset($this->faktur_image[$index]);
            $this->faktur_image = array_values($this->faktur_image);
        }
    }

    public function updatedCart($value, $name)
    {
        $name = (string) $name;
        $parts = explode('.', $name);
        if (count($parts) === 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            $this->updateRow($index, $field, $value);
        }
    }

    public function recalculateRow($index, $syncTaxPrice = true)
    {
        if (!isset($this->cart[$index])) {
            return;
        }

        $item = $this->cart[$index];
        $qty = (float) ($item['qty_received'] ?? 0);
        $dppPrice = (float) ($item['unit_price'] ?? 0);
        $taxPrice = (float) ($item['unit_price_tax'] ?? 0);
        $isTaxable = (bool) ($item['is_taxable'] ?? true);
        $taxMultiplier = 1 + ($this->taxRate / 100);

        if ($this->tax_type === 'non_tax') {
            $isTaxable = false;
        }

        if ($syncTaxPrice) {
            if ($this->tax_type === 'non_tax') {
                $this->cart[$index]['unit_price_tax'] = $dppPrice;
            } else {
                $this->cart[$index]['unit_price_tax'] = $isTaxable ? round($dppPrice * $taxMultiplier, 2) : $dppPrice;
            }
        }
        
        // Tentukan harga dasar baris sesuai Mode Pajak:
        // Pada mode Include PPN: baris dihitung dari Harga (+PPN) agar subtotal baris sama persis dengan cetakan faktur
        if ($this->tax_type === 'include' && $isTaxable) {
            $rowPrice = (float) ($this->cart[$index]['unit_price_tax'] ?? $taxPrice);
        } else {
            $rowPrice = $dppPrice;
        }

        $baseTotal = $qty * $rowPrice;
        $runningTotal = $baseTotal;

        foreach ([1, 2, 3] as $tier) {
            $val = (float) ($this->cart[$index]["discount_{$tier}"] ?? 0);
            $type = $this->cart[$index]["discount_{$tier}_type"] ?? 'percent';

            if ($val > 0) {
                if ($type === 'nominal') {
                    $runningTotal = max(0, $runningTotal - $val);
                } else {
                    $d = $runningTotal * ($val / 100);
                    $runningTotal = max(0, $runningTotal - $d);
                }
            }
        }

        $this->cart[$index]['subtotal'] = round($runningTotal, 2);

        // Update harga modal netto per unit (+PPN) untuk rujukan margin
        if ($this->tax_type === 'include') {
            $this->cart[$index]['unit_price_net_tax'] = $qty > 0 ? round($this->cart[$index]['subtotal'] / $qty, 2) : (float) ($this->cart[$index]['unit_price_tax'] ?? 0);
        } elseif ($this->tax_type === 'exclude' && $isTaxable) {
            $netDpp = $qty > 0 ? ($this->cart[$index]['subtotal'] / $qty) : $dppPrice;
            $this->cart[$index]['unit_price_net_tax'] = round($netDpp * $taxMultiplier, 2);
        } else {
            $this->cart[$index]['unit_price_net_tax'] = $qty > 0 ? round($this->cart[$index]['subtotal'] / $qty, 2) : $dppPrice;
        }

        $this->syncRowMargins($index);
        $this->calculateTotals();
    }

    public function toggleDiscountType($index, $tier = 1)
    {
        if (!isset($this->cart[$index])) {
            return;
        }
        $tier = in_array((int)$tier, [1, 2, 3]) ? (int)$tier : 1;
        $field = "discount_{$tier}_type";
        $current = $this->cart[$index][$field] ?? 'percent';
        $this->cart[$index][$field] = ($current === 'percent') ? 'nominal' : 'percent';
        $this->recalculateRow($index, false);
    }

    public function updatedTaxType($value)
    {
        $this->include_tax = ($value !== 'non_tax');
        foreach ($this->cart as $idx => $item) {
            $this->recalculateRow($idx, false);
            $this->syncRowMargins($idx);
        }
        $this->calculateTotals();
    }

    public function updatedIncludeTax()
    {
        $this->tax_type = $this->include_tax ? 'include' : 'non_tax';
        $this->updatedTaxType($this->tax_type);
    }

    public function updatedTaxAmount()
    {
        if ($this->tax_type === 'exclude') {
            $this->grandTotal = $this->dpp_amount + (float) $this->tax_amount;
        } else {
            $this->grandTotal = $this->subtotal + (float) $this->tax_amount;
        }
    }

    public function updatedDiscountSubtotal()
    {
        $this->calculateTotals();
    }

    public function updatedDiscountSubtotalType()
    {
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = collect($this->cart)->sum('qty_received');
        $this->subtotal = collect($this->cart)->sum('subtotal');

        $taxRate = (float) ($this->taxRate ?? 11);
        $taxMultiplier = 1 + ($taxRate / 100);

        // Hitung total penghematan diskon barang
        $totalGross = 0;
        foreach ($this->cart as $cItem) {
            $cQty = (float) ($cItem['qty_received'] ?? 0);
            $cPrice = ($this->tax_type === 'include' && ($cItem['is_taxable'] ?? true))
                ? (float) ($cItem['unit_price_tax'] ?? 0)
                : (float) ($cItem['unit_price'] ?? 0);
            $totalGross += ($cQty * $cPrice);
        }
        $this->totalItemDiscount = max(0, round($totalGross - $this->subtotal, 2));

        if ($this->tax_type === 'include') {
            // 1. MODE INCLUDE PPN (seperti Amidis, Danone/Aqua):
            // $this->subtotal adalah Total Bruto INCLUDE PPN (misal Amidis: 1.034.940,00)
            $discountAmount = 0;
            if ($this->discount_subtotal_type === 'percent') {
                $discountAmount = $this->subtotal * ($this->discount_subtotal / 100);
            } else {
                $discountAmount = (float) $this->discount_subtotal; // Nilai include PPN di faktur (misal 147.230,00)
            }

            $netTotal = $this->subtotal - $discountAmount; // misal 887.710,00
            $this->grandTotal = round($netTotal, 2);

            // Ekstraksi nilai DPP dan PPN mundur
            $this->dpp_amount = round($this->grandTotal / $taxMultiplier, 2); // misal 799.738,74
            $this->tax_amount = round($this->grandTotal - $this->dpp_amount, 2); // misal 87.971,26

        } elseif ($this->tax_type === 'exclude') {
            // 2. MODE EXCLUDE PPN (seperti Unilever, Wings, Mayora):
            // $this->subtotal adalah Total DPP (sebelum pajak)
            $discountAmount = 0;
            if ($this->discount_subtotal_type === 'percent') {
                $discountAmount = $this->subtotal * ($this->discount_subtotal / 100);
            } else {
                $discountAmount = (float) $this->discount_subtotal; // Diskon memotong DPP
            }

            $netDpp = $this->subtotal - $discountAmount;
            $this->dpp_amount = round($netDpp, 2);
            $this->tax_amount = round($netDpp * ($taxRate / 100), 2);
            $this->grandTotal = round($netDpp + $this->tax_amount, 2);

        } else {
            // 3. MODE NON-PPN (Bebas PPN / Supplier Non-PKP):
            $discountAmount = 0;
            if ($this->discount_subtotal_type === 'percent') {
                $discountAmount = $this->subtotal * ($this->discount_subtotal / 100);
            } else {
                $discountAmount = (float) $this->discount_subtotal;
            }

            $netTotal = $this->subtotal - $discountAmount;
            $this->grandTotal = round($netTotal, 2);
            $this->dpp_amount = $this->grandTotal;
            $this->tax_amount = 0;
        }
    }

    public function save()
    {
        $this->calculateTotals();

        $this->validate([
            'supplier_id' => 'required',
            'branch_id' => 'nullable',
            'receipt_date' => 'required|date',
            'receipt_number' => 'required|unique:goods_receipts,receipt_number,' . ($this->goodsReceipt ? $this->goodsReceipt->id : 'NULL'),
            'faktur_image.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
        }

        foreach ($this->cart as $idx => $item) {
            $this->syncRowMargins($idx);
        }

        foreach ($this->cart as $item) {
            foreach ([1, 2, 3] as $gol) {
                if (isset($item["margin_gol_{$gol}"]) && $item["margin_gol_{$gol}"] < 0) {
                    $hargaJual = (float) ($item["harga_jual_{$gol}"] ?? 0);
                    if ($hargaJual > 0) {
                        Notification::make()
                            ->title("Margin Golongan {$gol} produk '{$item['name']}' minus ({$item["margin_gol_{$gol}"]}%)!")
                            ->body("Silakan tampilkan kolom Harga Jual {$gol} (lewat Pilih Kolom) dan sesuaikan nilainya agar tidak rugi.")
                            ->danger()
                            ->send();
                        return;
                    }
                }
            }
        }

        $imagePaths = $this->existing_faktur_image;

        if ($this->faktur_image && is_array($this->faktur_image) && count($this->faktur_image) > 0) {
            foreach ($this->faktur_image as $file) {
                if ($file && !is_string($file)) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                        $image = $manager->decode($file->getRealPath());
                        
                        if ($image->width() > 1200) {
                            $image->scaleDown(width: 1200);
                        }
                        
                        $filename = 'faktur_receipts/' . uniqid() . '.jpg';
                        $fullPath = storage_path('app/public/' . $filename);
                        
                        if (!file_exists(storage_path('app/public/faktur_receipts'))) {
                            mkdir(storage_path('app/public/faktur_receipts'), 0755, true);
                        }

                        $image->encode(new \Intervention\Image\Encoders\JpegEncoder(75))->save($fullPath);
                        $imagePaths[] = $filename;
                    } else {
                        $imagePaths[] = $file->store('faktur_receipts', 'public');
                    }
                }
            }
        }

        $gr = null;
        DB::transaction(function () use (&$gr, $imagePaths) {
            $data = [
                'purchase_order_id' => $this->purchase_order_id,
                'supplier_id' => $this->supplier_id,
                'supplier_division_id' => empty($this->supplier_division_id) ? null : $this->supplier_division_id,
                'branch_id' => $this->branch_id,
                'receipt_number' => $this->receipt_number,
                'receipt_date' => $this->receipt_date,
                'due_date' => $this->due_date,
                'received_by' => auth()->user()->name,
                'faktur_supplier' => $this->faktur_supplier,
                'faktur_image' => empty($imagePaths) ? null : $imagePaths,
                'total_amount' => $this->grandTotal,
                'discount_subtotal' => $this->discount_subtotal,
                'discount_subtotal_type' => $this->discount_subtotal_type,
                'include_tax' => ($this->tax_type !== 'non_tax'),
                'tax_type' => $this->tax_type,
                'tax_amount' => $this->tax_amount,
                'status' => 'RECEIVED',
                'payment_method' => $this->payment_method,
                'payment_status' => $this->payment_method === 'tempo' ? 'UNPAID' : 'PAID',
                'paid_amount' => $this->payment_method === 'tempo' ? 0 : $this->grandTotal,
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

            $taxRate = (float) ($this->taxRate ?? \App\Services\RetailIntelligenceService::getActiveTaxRate());
            $taxMultiplier = 1 + ($taxRate / 100);

            foreach ($this->cart as $item) {
                $qty = (float) $item['qty_received'];
                $itemSubtotal = (float) $item['subtotal'];

                if ($this->tax_type === 'include') {
                    $itemSubtotalDpp = round($itemSubtotal / $taxMultiplier, 2);
                    $itemUnitPriceDpp = round(((float)$item['unit_price_tax']) / $taxMultiplier, 4);

                    // Modal barang diambil dari harga netto baris faktur (+PPN)
                    $costPriceTax = $qty > 0 ? round($itemSubtotal / $qty, 2) : (float) $item['unit_price_tax'];
                    $netPrice = round($costPriceTax / $taxMultiplier, 4);
                } elseif ($this->tax_type === 'exclude') {
                    $itemSubtotalDpp = $itemSubtotal;
                    $itemUnitPriceDpp = (float) $item['unit_price'];

                    // Modal barang diambil dari harga netto baris DPP, lalu ditambah PPN 11%
                    $netPrice = $qty > 0 ? round($itemSubtotal / $qty, 4) : (float) $item['unit_price'];
                    $costPriceTax = round($netPrice * $taxMultiplier, 2);
                } else {
                    $itemSubtotalDpp = $itemSubtotal;
                    $itemUnitPriceDpp = (float) $item['unit_price'];

                    // Non PPN: murni harga netto baris
                    $netPrice = $qty > 0 ? round($itemSubtotal / $qty, 2) : (float) $item['unit_price'];
                    $costPriceTax = $netPrice;
                }

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['qty_ordered'],
                    'quantity_received' => $item['qty_received'],
                    'unit_price' => $itemUnitPriceDpp,
                    'discount_1' => $item['discount_1'] ?? 0,
                    'discount_1_type' => $item['discount_1_type'] ?? 'percent',
                    'discount_2' => $item['discount_2'] ?? 0,
                    'discount_2_type' => $item['discount_2_type'] ?? 'percent',
                    'discount_3' => $item['discount_3'] ?? 0,
                    'discount_3_type' => $item['discount_3_type'] ?? 'percent',
                    'subtotal' => $itemSubtotalDpp
                ]);

                $product = Product::find($item['product_id']);
                $isTaxable = (bool) ($item['is_taxable'] ?? ($product?->is_taxable ?? true));
                $costBasisMargin = $costPriceTax > 0 ? $costPriceTax : $netPrice;

                // 1. Selalu update Produk Global (Master Data) agar harga global tetap up-to-date
                if ($product) {
                    $updateData = [
                        'cost_price' => $netPrice,
                        'cost_price_tax' => $costPriceTax,
                    ];
                    if (auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE')) {
                        $updateData['harga_jual_1'] = $item['harga_jual_1'] ?? $product->harga_jual_1;
                        $updateData['harga_jual_2'] = $item['harga_jual_2'] ?? $product->harga_jual_2;
                        $updateData['harga_jual_3'] = $item['harga_jual_3'] ?? $product->harga_jual_3;
                        
                        foreach([1, 2, 3] as $i) {
                            $hj = (float) $updateData["harga_jual_{$i}"];
                            if ($hj > 0 && $costBasisMargin > 0) {
                                $updateData["margin_gol_{$i}"] = round((($hj - $costBasisMargin) / $costBasisMargin) * 100, 2);
                            } else {
                                $updateData["margin_gol_{$i}"] = (float) ($item["margin_gol_{$i}"] ?? 0);
                            }
                        }
                        $updateData['selling_price'] = $updateData['harga_jual_1'];
                    }
                    $product->update($updateData);
                }

                // 2. Jika cabang dipilih, update juga harga spesifik cabang tersebut
                if (!empty($this->branch_id)) {
                    $stock = Stock::where('product_id', $item['product_id'])->where('branch_id', $this->branch_id)->first();
                    if ($stock) {
                        $updateData = [
                            'cost_price' => $netPrice,
                            'cost_price_tax' => $costPriceTax,
                        ];
                        if (auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE')) {
                            $updateData['harga_jual_1'] = $item['harga_jual_1'] ?? $stock->harga_jual_1;
                            $updateData['harga_jual_2'] = $item['harga_jual_2'] ?? $stock->harga_jual_2;
                            $updateData['harga_jual_3'] = $item['harga_jual_3'] ?? $stock->harga_jual_3;
                            
                            foreach([1, 2, 3] as $i) {
                                $hj = (float) $updateData["harga_jual_{$i}"];
                                if ($hj > 0 && $costBasisMargin > 0) {
                                    $updateData["margin_gol_{$i}"] = round((($hj - $costBasisMargin) / $costBasisMargin) * 100, 2);
                                } else {
                                    $updateData["margin_gol_{$i}"] = (float) ($item["margin_gol_{$i}"] ?? 0);
                                }
                            }
                            $updateData['selling_price'] = $updateData['harga_jual_1'];
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

        $this->clearDraft();
        
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
        return view('livewire.goods-receipt-pos', [
            'branches' => Branch::select('id', 'name')->get(),
            'suppliers' => Supplier::where('is_active', true)
                ->when($this->supplier_id, fn($q) => $q->orWhere('id', $this->supplier_id))
                ->select('id', 'name', 'gr_requires_po')
                ->orderBy('name', 'asc')
                ->get(),
            'purchaseOrders' => $this->purchaseOrders,
        ]);
    }
}


