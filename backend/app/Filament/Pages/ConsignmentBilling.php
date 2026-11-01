<?php

namespace App\Filament\Pages;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\GoodsReceiptItem;
use App\Models\TransactionItem;
use App\Models\PurchaseReturnItem;
use App\Models\Kontrabon;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ConsignmentBilling extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    public static function getNavigationGroup(): ?string
    {
        return 'KEUANGAN';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-currency-dollar';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Penagihan Konsinyasi & Sellout';
    }

    public static function getNavigationLabel(): string
    {
        return 'Penagihan Konsinyasi';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    protected string $view = 'filament.pages.consignment-billing';

    public ?string $branch_id = null;
    public ?string $supplier_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;

    public array $selloutData = [];
    public float $totalTagihan = 0;

    public function mount()
    {
        $this->branch_id = auth()->user()->branch_id;
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Filter Laporan Sellout & Tagihan')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Cabang')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->default(auth()->user()->branch_id)
                            ->disabled(auth()->user()->branch_id !== null)
                            ->afterStateUpdated(fn () => $this->calculateSellout()),
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier Konsinyasi')
                            ->options(Supplier::where('is_active', true)->where('is_consignment', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->calculateSellout()),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->calculateSellout()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai Tanggal (Cut-Off)')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->calculateSellout()),
                    ])
            ]);
    }

    public function calculateSellout()
    {
        if (!$this->branch_id || !$this->supplier_id || !$this->start_date || !$this->end_date) {
            $this->selloutData = [];
            $this->totalTagihan = 0;
            return;
        }

        $branchId = $this->branch_id;
        $supplierId = $this->supplier_id;

        $products = Product::where(function ($q) use ($branchId, $supplierId) {
            $q->whereHas('stocks', function ($sq) use ($branchId, $supplierId) {
                $sq->where('branch_id', $branchId)->where('supplier_id', $supplierId);
            })->orWhere(function ($sq) use ($branchId, $supplierId) {
                $sq->where('products.supplier_id', $supplierId)
                   ->whereDoesntHave('stocks', function ($stq) use ($branchId, $supplierId) {
                       $stq->where('branch_id', $branchId)
                           ->whereNotNull('supplier_id')
                           ->where('supplier_id', '!=', $supplierId);
                   });
            });
        })->get();

        $data = [];
        $totalTagihan = 0;
        $productIds = $products->pluck('id')->toArray();

        if (empty($productIds)) {
            $this->reportData = [];
            $this->grandTotalTagihan = 0;
            return;
        }

        // 1. Batch load Penerimaan
        $receivedMap = GoodsReceiptItem::join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->whereIn('goods_receipt_items.product_id', $productIds)
            ->where('goods_receipts.branch_id', $this->branch_id)
            ->whereBetween('goods_receipts.receipt_date', [$this->start_date, $this->end_date])
            ->groupBy('goods_receipt_items.product_id')
            ->select('goods_receipt_items.product_id', \Illuminate\Support\Facades\DB::raw('SUM(goods_receipt_items.quantity_received) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // 2. Batch load Terjual POS di Periode Tersebut
        $soldMap = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereIn('transaction_items.product_id', $productIds)
            ->where('transactions.branch_id', $this->branch_id)
            ->where('transactions.is_voided', false)
            ->whereBetween('transactions.transaction_date', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59'])
            ->groupBy('transaction_items.product_id')
            ->select('transaction_items.product_id', \Illuminate\Support\Facades\DB::raw('SUM(transaction_items.quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // 3. Batch load Retur Beli
        $returnedMap = PurchaseReturnItem::join('purchase_returns', 'purchase_return_items.purchase_return_id', '=', 'purchase_returns.id')
            ->whereIn('purchase_return_items.product_id', $productIds)
            ->where('purchase_returns.branch_id', $this->branch_id)
            ->whereBetween('purchase_returns.return_date', [$this->start_date, $this->end_date])
            ->groupBy('purchase_return_items.product_id')
            ->select('purchase_return_items.product_id', \Illuminate\Support\Facades\DB::raw('SUM(purchase_return_items.quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // 4. Batch load Unbilled POS Items
        $allUnbilledPosItems = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereIn('transaction_items.product_id', $productIds)
            ->where('transactions.branch_id', $this->branch_id)
            ->whereNull('transaction_items.kontrabon_id')
            ->where('transactions.is_voided', false)
            ->where('transactions.transaction_date', '<=', $this->end_date . ' 23:59:59')
            ->select('transaction_items.id', 'transaction_items.product_id', 'transaction_items.quantity')
            ->get()
            ->groupBy('product_id');

        $posItemIds = $allUnbilledPosItems->flatten(1)->pluck('id')->filter()->toArray();
        $posBatchCogsMap = [];
        if (!empty($posItemIds)) {
            $posBatchCogsMap = \Illuminate\Support\Facades\DB::table('stock_batch_deductions as sbd')
                ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                ->whereIn('sbd.transaction_item_id', $posItemIds)
                ->groupBy('sbd.transaction_item_id')
                ->select('sbd.transaction_item_id', \Illuminate\Support\Facades\DB::raw('SUM(sbd.quantity * sb.cost_price) as total_cogs'))
                ->pluck('total_cogs', 'transaction_item_id')
                ->toArray();
        }

        // 5. Batch load Unbilled Ecommerce Items
        $allUnbilledEcomItems = \App\Models\EcommerceOrderItem::join('ecommerce_orders', 'ecommerce_order_items.ecommerce_order_id', '=', 'ecommerce_orders.id')
            ->whereIn('ecommerce_order_items.product_id', $productIds)
            ->where('ecommerce_orders.branch_id', $this->branch_id)
            ->whereNull('ecommerce_order_items.kontrabon_id')
            ->where('ecommerce_orders.status', 'COMPLETED')
            ->where('ecommerce_orders.created_at', '<=', $this->end_date . ' 23:59:59')
            ->select('ecommerce_order_items.id', 'ecommerce_order_items.product_id', 'ecommerce_order_items.quantity')
            ->get()
            ->groupBy('product_id');

        $ecomItemIds = $allUnbilledEcomItems->flatten(1)->pluck('id')->filter()->toArray();
        $ecomBatchCogsMap = [];
        if (!empty($ecomItemIds)) {
            $ecomBatchCogsMap = \Illuminate\Support\Facades\DB::table('stock_batch_deductions as sbd')
                ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                ->whereIn('sbd.ecommerce_order_item_id', $ecomItemIds)
                ->groupBy('sbd.ecommerce_order_item_id')
                ->select('sbd.ecommerce_order_item_id', \Illuminate\Support\Facades\DB::raw('SUM(sbd.quantity * sb.cost_price) as total_cogs'))
                ->pluck('total_cogs', 'ecommerce_order_item_id')
                ->toArray();
        }

        // 6. Batch load Stocks
        $stockMap = \App\Models\Stock::whereIn('product_id', $productIds)
            ->where('branch_id', $this->branch_id)
            ->get()
            ->keyBy('product_id');

        foreach ($products as $product) {
            $received = (float) ($receivedMap[$product->id] ?? 0);
            $soldQty = (float) ($soldMap[$product->id] ?? 0);
            $returned = (float) ($returnedMap[$product->id] ?? 0);

            $unbilledPosItems = $allUnbilledPosItems->get($product->id, collect());
            $unbilledSold = (float) $unbilledPosItems->sum('quantity');

            $unbilledEcomItems = $allUnbilledEcomItems->get($product->id, collect());
            $unbilledEcommerceSold = (float) $unbilledEcomItems->sum('quantity');

            $soldQty += $unbilledEcommerceSold; // Add to display total sold
            $unbilledSold += $unbilledEcommerceSold; // Add to unbilled calculation

            // Tagihan dihitung berdasarkan HPP dari Batch
            $amountOwed = 0;
            $stock = $stockMap->get($product->id);
            // Prioritas fallback jika transaksi tidak memiliki potongan batch FIFO: HPP dari Stok Cabang
            if ($stock && (float) $stock->cost_price > 0) {
                $fallbackPrice = (float) (!$product->is_taxable 
                    ? $stock->cost_price 
                    : ($stock->cost_price_tax > 0 ? $stock->cost_price_tax : $stock->cost_price));
            } else {
                // Cadangan terakhir: Master Produk
                $fallbackPrice = (float) (!$product->is_taxable 
                    ? ($product->cost_price ?? 0) 
                    : ($product->cost_price_tax > 0 ? $product->cost_price_tax : ($product->cost_price ?? 0)));
            }

            foreach ($unbilledPosItems as $item) {
                $batchCogs = (float) ($posBatchCogsMap[$item->id] ?? 0);

                if ($item->quantity > 0) {
                    $amountOwed += $batchCogs > 0 ? $batchCogs : ($item->quantity * $fallbackPrice);
                } else {
                    $deduction = $batchCogs > 0 ? $batchCogs : (abs($item->quantity) * $fallbackPrice);
                    $amountOwed -= $deduction;
                }
            }

            foreach ($unbilledEcomItems as $item) {
                $batchCogs = (float) ($ecomBatchCogsMap[$item->id] ?? 0);

                if ($item->quantity > 0) {
                    $amountOwed += $batchCogs > 0 ? $batchCogs : ($item->quantity * $fallbackPrice);
                } else {
                    $deduction = $batchCogs > 0 ? $batchCogs : (abs($item->quantity) * $fallbackPrice);
                    $amountOwed -= $deduction;
                }
            }
            
            $averageCostPrice = $unbilledSold > 0 ? ($amountOwed / $unbilledSold) : $fallbackPrice;

            if ($received > 0 || $soldQty > 0 || $returned > 0 || $unbilledSold > 0) {
                $data[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'received' => $received,
                    'sold' => $soldQty,
                    'returned' => $returned,
                    'unbilled_qty' => $unbilledSold,
                    'cost_price' => $averageCostPrice,
                    'amount_owed' => $amountOwed,
                ];
                $totalTagihan += $amountOwed;
            }
        }

        $this->selloutData = $data;
        $this->totalTagihan = $totalTagihan;
    }

    public function createKontrabon()
    {
        if (empty($this->selloutData) || $this->totalTagihan <= 0) {
            Notification::make()->title('Tidak ada transaksi konsinyasi untuk ditagih.')->warning()->send();
            return;
        }

        DB::beginTransaction();
        try {
            $orgId = auth()->user()->organization_id ?? 1;
            $kontrabonNo = 'KBC-' . date('YmdHis');

            $kontrabon = Kontrabon::create([
                'branch_id' => $this->branch_id,
                'supplier_id' => $this->supplier_id,
                'kontrabon_number' => $kontrabonNo,
                'tanggal_kontrabon' => date('Y-m-d'),
                'tanggal_jatuh_tempo' => date('Y-m-d', strtotime('+7 days')),
                'total_amount' => $this->totalTagihan,
                'paid_amount' => 0,
                'notes' => "Penagihan Konsinyasi Periode " . $this->start_date . " s/d " . $this->end_date,
                'status' => 'UNPAID',
                'created_by_id' => auth()->id(),
            ]);

            $productIds = collect($this->selloutData)->pluck('product_id')->toArray();
            
            TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->whereIn('transaction_items.product_id', $productIds)
                ->where('transactions.branch_id', $this->branch_id)
                ->whereNull('transaction_items.kontrabon_id')
                ->where('transactions.is_voided', false)
                ->where('transactions.transaction_date', '<=', $this->end_date . ' 23:59:59')
                ->update(['transaction_items.kontrabon_id' => $kontrabon->id]);
                
            \App\Models\EcommerceOrderItem::join('ecommerce_orders', 'ecommerce_order_items.ecommerce_order_id', '=', 'ecommerce_orders.id')
                ->whereIn('ecommerce_order_items.product_id', $productIds)
                ->where('ecommerce_orders.branch_id', $this->branch_id)
                ->whereNull('ecommerce_order_items.kontrabon_id')
                ->where('ecommerce_orders.status', 'COMPLETED')
                ->where('ecommerce_orders.created_at', '<=', $this->end_date . ' 23:59:59')
                ->update(['ecommerce_order_items.kontrabon_id' => $kontrabon->id]);

            DB::commit();

            Notification::make()->title('Kontrabon Konsinyasi Berhasil Dibuat')->success()->send();
            $this->calculateSellout();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Terjadi Kesalahan: ' . $e->getMessage())->danger()->send();
        }
    }
}
