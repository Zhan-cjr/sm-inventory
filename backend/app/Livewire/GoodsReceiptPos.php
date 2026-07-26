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
    public $payment_method = 'tempo';
    public $purchase_order_id;
    public $include_tax = true;
    public $tax_amount = 0;
    public $cetak_nota = false;

    public $visibleColumns = ['barcode', 'name', 'qty_ordered', 'qty_received', 'unit_price', 'harga_jual_1', 'margin_gol_1', 'discount_1', 'discount_2', 'discount_3', 'subtotal'];

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
            $this->receipt_date = $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('Y-m-d') : null;
            $this->due_date = $goodsReceipt->due_date ? $goodsReceipt->due_date->format('Y-m-d') : null;
            $this->faktur_supplier = $goodsReceipt->faktur_supplier;
            $this->branch_id = $goodsReceipt->branch_id;
            $this->notes = $goodsReceipt->notes;
            $this->supplier_id = $goodsReceipt->supplier_id;
            $this->payment_method = $goodsReceipt->payment_method ?? 'tempo';
            $this->purchase_order_id = $goodsReceipt->purchase_order_id;
            $this->include_tax = $goodsReceipt->include_tax;
            $this->tax_amount = $goodsReceipt->tax_amount;
            $this->existing_faktur_image = is_array($goodsReceipt->faktur_image) ? $goodsReceipt->faktur_image : ($goodsReceipt->faktur_image ? [$goodsReceipt->faktur_image] : []);
            $this->faktur_image = [];

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
            $this->due_date = date('Y-m-d');
            $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;

            // Load draft if not editing existing GR
            $this->loadDraft();
        }

        if ($this->supplier_id) {
            $supplier = Supplier::find($this->supplier_id);
            $this->gr_requires_po = (bool) ($supplier?->gr_requires_po);
        }

        $this->calculateTotals();
    }

    public function dehydrate()
    {
        $this->saveDraft();
    }

    public $only_latest_po = false;
    public $gr_requires_po = false;

    public function updatedSupplierId($value)
    {
        $this->recalculateDueDate();
        if ($value) {
            $supplier = Supplier::find($value);
            $this->gr_requires_po = (bool) ($supplier?->gr_requires_po);
        } else {
            $this->gr_requires_po = false;
        }
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
                $this->recalculateDueDate();
                
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
            if (!auth()->user()->hasCustomAuthorization('BYPASS_GR_PO_REQUIRED')) {
                $this->searchResults = [];
                return;
            }
        }

        if (!empty($this->purchase_order_id) || (!empty($this->goodsReceipt) && !empty($this->goodsReceipt->warehouse_check_id))) {
            $this->searchResults = [];
            return;
        }

        if (strlen($value) >= 2) {
            $this->searchResults = Product::query()
                ->select('products.id', 'products.sku', 'products.barcode', 'products.name', 'products.cost_price')
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
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu.')->warning()->send();
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
            Notification::make()->title('Pilih Lokasi Cabang terlebih dahulu.')->warning()->send();
            return;
        }

        if (empty($this->supplier_id)) {
            Notification::make()->title('Pilih Pemasok terlebih dahulu.')->warning()->send();
            return;
        }

        if ($this->gr_requires_po && empty($this->purchase_order_id)) {
            if (!auth()->user()->hasCustomAuthorization('BYPASS_GR_PO_REQUIRED')) {
                Notification::make()->title('Penerimaan barang wajib dengan PO untuk Pemasok ini.')->warning()->send();
                return;
            }
        }

        if (!empty($this->purchase_order_id)) {
            Notification::make()->title('Tidak dapat menambah barang baru saat menggunakan PO.')->warning()->send();
            return;
        }

        if (!empty($this->goodsReceipt) && !empty($this->goodsReceipt->warehouse_check_id)) {
            Notification::make()->title('Tidak dapat menambah barang baru pada penerimaan dari Pengecekan Gudang.')->warning()->send();
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

    public $enable_edit_total = false;

    // AI Scanner specific properties
    public $scan_image;
    public $scan_loading = false;

    public function scanInvoiceAction()
    {
        $this->validate([
            'scan_image' => 'required|image|max:10240', // max 10MB
        ]);

        if (!$this->supplier_id) {
            Notification::make()->title('Pilih Supplier Terlebih Dahulu')->warning()->send();
            return;
        }

        $this->scan_loading = true;

        try {
            // Get original path
            $filePath = $this->scan_image->getRealPath();
            $fileContent = fopen($filePath, 'r');

            $response = \Illuminate\Support\Facades\Http::timeout(120)->attach(
                'file', $fileContent, $this->scan_image->getClientOriginalName()
            )->post('http://localhost:8001/api/v1/ai/scan-invoice', [
                'supplier_id' => $this->supplier_id
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $productId = $item['product_id'] ?? null;
                        
                        if ($productId) {
                            $product = Product::find($productId);
                            if ($product) {
                                // Find in cart
                                $idx = collect($this->cart)->search(fn($c) => $c['product_id'] == $product->id);
                                if ($idx !== false) {
                                    $this->cart[$idx]['qty_received'] = $item['qty'];
                                    $this->cart[$idx]['unit_price'] = $item['unit_price'];
                                    $this->cart[$idx]['discount_1'] = $item['discount_1'] ?? 0;
                                    $this->cart[$idx]['discount_2'] = $item['discount_2'] ?? 0;
                                    $this->cart[$idx]['discount_3'] = $item['discount_3'] ?? 0;
                                    $this->recalculateRow($idx);
                                } else {
                                    // Add to cart with scanned data
                                    $stock = null;
                                    if ($this->branch_id) {
                                        $stock = Stock::where('product_id', $product->id)->where('branch_id', $this->branch_id)->first();
                                    }
                                    
                                    $this->cart[] = [
                                        'product_id' => $product->id,
                                        'sku' => $product->sku,
                                        'barcode' => $product->barcode,
                                        'name' => $product->name,
                                        'qty_ordered' => 0,
                                        'qty_received' => $item['qty'],
                                        'unit_price' => $item['unit_price'],
                                        'harga_jual_1' => ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($product->harga_jual_1 ?? 0),
                                        'margin_gol_1' => ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($product->margin_gol_1 ?? 0),
                                        'harga_jual_2' => ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($product->harga_jual_2 ?? 0),
                                        'margin_gol_2' => ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($product->margin_gol_2 ?? 0),
                                        'harga_jual_3' => ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($product->harga_jual_3 ?? 0),
                                        'margin_gol_3' => ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($product->margin_gol_3 ?? 0),
                                        'discount_1' => $item['discount_1'] ?? 0,
                                        'discount_2' => $item['discount_2'] ?? 0,
                                        'discount_3' => $item['discount_3'] ?? 0,
                                        'subtotal' => $item['subtotal'] ?? 0,
                                        'needs_mapping' => false
                                    ];
                                    $this->recalculateRow(count($this->cart) - 1);
                                }
                            }
                        } else {
                            $this->cart[] = [
                                'product_id' => null,
                                'raw_name' => $item['raw_name'],
                                'sku' => '-',
                                'barcode' => '-',
                                'name' => '⚠️ ' . $item['raw_name'] . ' (Pilih Produk)',
                                'qty_ordered' => 0,
                                'qty_received' => $item['qty'],
                                'unit_price' => $item['unit_price'],
                                'harga_jual_1' => 0,
                                'margin_gol_1' => 0,
                                'harga_jual_2' => 0,
                                'margin_gol_2' => 0,
                                'harga_jual_3' => 0,
                                'margin_gol_3' => 0,
                                'discount_1' => $item['discount_1'] ?? 0,
                                'discount_2' => $item['discount_2'] ?? 0,
                                'discount_3' => $item['discount_3'] ?? 0,
                                'subtotal' => $item['subtotal'] ?? 0,
                                'needs_mapping' => true
                            ];
                        }
                    }
                    $this->calculateTotals();
                    Notification::make()->title('Scan AI Selesai')->success()->send();
                } else {
                    Notification::make()->title('AI gagal mendeteksi item faktur')->warning()->send();
                }
            } else {
                Notification::make()->title('Gagal menghubungi AI Service')->danger()->send();
            }

        } catch (\Exception $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }

        $this->scan_loading = false;
        $this->scan_image = null; // reset
    }

    public function mapProduct($index, $productId)
    {
        $product = Product::find($productId);
        if (!$product) return;
        
        $item = $this->cart[$index];
        $rawName = $item['raw_name'] ?? null;
        
        if ($rawName && $this->supplier_id) {
            \Illuminate\Support\Facades\DB::table('supplier_item_mappings')->insertOrIgnore([
                'id' => \Illuminate\Support\Str::uuid(),
                'supplier_id' => $this->supplier_id,
                'raw_name' => $rawName,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Replace the unmapped item with the real product
        $stock = null;
        if ($this->branch_id) {
            $stock = Stock::where('product_id', $product->id)->where('branch_id', $this->branch_id)->first();
        }

        $this->cart[$index]['product_id'] = $product->id;
        $this->cart[$index]['sku'] = $product->sku;
        $this->cart[$index]['barcode'] = $product->barcode;
        $this->cart[$index]['name'] = $product->name;
        $this->cart[$index]['needs_mapping'] = false;
        
        $this->cart[$index]['harga_jual_1'] = ($stock && $stock->harga_jual_1 > 0) ? $stock->harga_jual_1 : ($product->harga_jual_1 ?? 0);
        $this->cart[$index]['margin_gol_1'] = ($stock && $stock->margin_gol_1 > 0) ? $stock->margin_gol_1 : ($product->margin_gol_1 ?? 0);
        $this->cart[$index]['harga_jual_2'] = ($stock && $stock->harga_jual_2 > 0) ? $stock->harga_jual_2 : ($product->harga_jual_2 ?? 0);
        $this->cart[$index]['margin_gol_2'] = ($stock && $stock->margin_gol_2 > 0) ? $stock->margin_gol_2 : ($product->margin_gol_2 ?? 0);
        $this->cart[$index]['harga_jual_3'] = ($stock && $stock->harga_jual_3 > 0) ? $stock->harga_jual_3 : ($product->harga_jual_3 ?? 0);
        $this->cart[$index]['margin_gol_3'] = ($stock && $stock->margin_gol_3 > 0) ? $stock->margin_gol_3 : ($product->margin_gol_3 ?? 0);
        
        $this->recalculateRow($index);
        $this->calculateTotals();
        
        Notification::make()->title('Produk berhasil dipetakan!')->success()->send();
    }

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
            $index = $parts[0];
            $field = $parts[1];
            
            if ($field === 'subtotal' && $this->enable_edit_total) {
                $qty = (float) ($this->cart[$index]['qty_received'] ?? 0);
                $subtotal = (float) $value;
                if ($qty > 0) {
                    $d3 = (float) ($this->cart[$index]['discount_3'] ?? 0) / 100;
                    $d2 = (float) ($this->cart[$index]['discount_2'] ?? 0) / 100;
                    $d1 = (float) ($this->cart[$index]['discount_1'] ?? 0) / 100;
                    $f3 = 1 - $d3; if ($f3 == 0) $f3 = 1;
                    $f2 = 1 - $d2; if ($f2 == 0) $f2 = 1;
                    $f1 = 1 - $d1; if ($f1 == 0) $f1 = 1;
                    $baseTotal = $subtotal / $f3 / $f2 / $f1;
                    $this->cart[$index]['unit_price'] = round($baseTotal / $qty, 2);
                }
            }

            $this->recalculateRow($index);
            
            $item = $this->cart[$index];
            $qty = (float) ($item['qty_received'] ?? 0);
            $netPrice = $qty > 0 ? ((float)($item['subtotal'] ?? 0) / $qty) : (float) ($item['unit_price'] ?? 0);
            
            $product = \App\Models\Product::find($item['product_id']);
            $costPriceTax = ($this->include_tax && $product && $product->is_taxable) ? round($netPrice * 1.11, 2) : $netPrice;

            if (in_array($field, ['margin_gol_1', 'margin_gol_2', 'margin_gol_3'])) {
                $gol = substr($field, -1);
                $margin = (float) $value;
                if ($costPriceTax > 0) {
                    $this->cart[$index]["harga_jual_{$gol}"] = round($costPriceTax * (1 + ($margin / 100)), 2);
                }
            } elseif (in_array($field, ['harga_jual_1', 'harga_jual_2', 'harga_jual_3'])) {
                $gol = substr($field, -1);
                $sellingPrice = (float) $value;
                if ($sellingPrice > 0 && $costPriceTax > 0) {
                    $this->cart[$index]["margin_gol_{$gol}"] = round((($sellingPrice - $costPriceTax) / $costPriceTax) * 100, 2);
                } else {
                    $this->cart[$index]["margin_gol_{$gol}"] = 0;
                }
            } else {
                foreach([1, 2, 3] as $i) {
                    $sellingPrice = (float) ($this->cart[$index]["harga_jual_{$i}"] ?? 0);
                    if ($sellingPrice > 0 && $costPriceTax > 0) {
                        $this->cart[$index]["margin_gol_{$i}"] = round((($sellingPrice - $costPriceTax) / $costPriceTax) * 100, 2);
                    } else {
                        $this->cart[$index]["margin_gol_{$i}"] = 0;
                    }
                }
            }

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
            $qty = (float) ($item['qty_received'] ?? 0);
            $netPrice = $qty > 0 ? ((float)($item['subtotal'] ?? 0) / $qty) : (float) ($item['unit_price'] ?? 0);
            
            $product = \App\Models\Product::find($item['product_id']);
            $costPriceTax = ($this->include_tax && $product && $product->is_taxable) ? round($netPrice * 1.11, 2) : $netPrice;
            
            foreach([1, 2, 3] as $i) {
                $sellingPrice = (float) ($this->cart[$index]["harga_jual_{$i}"] ?? 0);
                if ($sellingPrice > 0 && $costPriceTax > 0) {
                    $this->cart[$index]["margin_gol_{$i}"] = round((($sellingPrice - $costPriceTax) / $costPriceTax) * 100, 2);
                } else {
                    $this->cart[$index]["margin_gol_{$i}"] = 0;
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
            'faktur_image.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong!')->danger()->send();
            return;
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
                'branch_id' => $this->branch_id,
                'receipt_number' => $this->receipt_number,
                'receipt_date' => $this->receipt_date,
                'due_date' => $this->due_date,
                'received_by' => auth()->user()->name,
                'faktur_supplier' => $this->faktur_supplier,
                'faktur_image' => empty($imagePaths) ? null : $imagePaths,
                'total_amount' => $this->grandTotal,
                'include_tax' => $this->include_tax,
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

                $netPrice = $item['qty_received'] > 0 ? ($item['subtotal'] / $item['qty_received']) : $item['unit_price'];
                $costPriceTax = $this->include_tax ? round($netPrice * $taxMultiplier, 2) : $netPrice;

                // 1. Selalu update Produk Global (Master Data) agar harga global tetap up-to-date
                $product = Product::find($item['product_id']);
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
                            if ($hj > 0 && $costPriceTax > 0) {
                                $updateData["margin_gol_{$i}"] = round((($hj - $costPriceTax) / $costPriceTax) * 100, 2);
                            } else {
                                $updateData["margin_gol_{$i}"] = 0;
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
                                if ($hj > 0 && $costPriceTax > 0) {
                                    $updateData["margin_gol_{$i}"] = round((($hj - $costPriceTax) / $costPriceTax) * 100, 2);
                                } else {
                                    $updateData["margin_gol_{$i}"] = 0;
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
        $purchaseOrders = collect();
        if ($this->supplier_id) {
            $purchaseOrdersQuery = PurchaseOrder::where(function ($q) {
                $q->whereIn('status', ['APPROVED', 'approved', 'PARTIALLY_RECEIVED', 'partially_received'])
                  ->whereHas('warehouseChecks', function ($qc) {
                      $qc->where('status', 'approved');
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
                $purchaseOrdersQuery->orWhere('id', $this->purchase_order_id);
            } else if ($this->only_latest_po) {
                $purchaseOrdersQuery->latest('created_at')->limit(1);
            }

            $purchaseOrders = $purchaseOrdersQuery->get();
        }

        return view('livewire.goods-receipt-pos', [
            'branches' => Branch::all(),
            'suppliers' => Supplier::all(),
            'purchaseOrders' => $purchaseOrders,
        ]);
    }
}


