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

        $products = Product::where('supplier_id', $this->supplier_id)->get();

        $data = [];
        $totalTagihan = 0;

        foreach ($products as $product) {
            // 1. Penerimaan
            $received = GoodsReceiptItem::join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->where('goods_receipt_items.product_id', $product->id)
                ->where('goods_receipts.branch_id', $this->branch_id)
                ->whereBetween('goods_receipts.receipt_date', [$this->start_date, $this->end_date])
                ->sum('goods_receipt_items.quantity_received');

            // 2. Terjual di Periode Tersebut
            $soldQty = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->where('transaction_items.product_id', $product->id)
                ->where('transactions.branch_id', $this->branch_id)
                ->where('transactions.is_voided', false)
                ->whereBetween('transactions.transaction_date', [$this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59'])
                ->sum('transaction_items.quantity');
            
            // 3. Retur Beli (Ke Supplier)
            $returned = PurchaseReturnItem::join('purchase_returns', 'purchase_return_items.purchase_return_id', '=', 'purchase_returns.id')
                ->where('purchase_return_items.product_id', $product->id)
                ->where('purchase_returns.branch_id', $this->branch_id)
                ->whereBetween('purchase_returns.return_date', [$this->start_date, $this->end_date])
                ->sum('purchase_return_items.quantity');

            // 4. Hitung Unbilled Sold (Belum Pernah Ditagih sampai Cut-Off)
            $unbilledSold = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->where('transaction_items.product_id', $product->id)
                ->where('transactions.branch_id', $this->branch_id)
                ->whereNull('transaction_items.kontrabon_id')
                ->where('transactions.is_voided', false)
                ->where('transactions.transaction_date', '<=', $this->end_date . ' 23:59:59')
                ->sum('transaction_items.quantity');

            // Tagihan dihitung berdasarkan HPP
            $amountOwed = $unbilledSold * $product->cost_price;

            if ($received > 0 || $soldQty > 0 || $returned > 0 || $unbilledSold > 0) {
                $data[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'received' => $received,
                    'sold' => $soldQty,
                    'returned' => $returned,
                    'unbilled_qty' => $unbilledSold,
                    'cost_price' => $product->cost_price,
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
                'notes' => "Penagihan Konsinyasi Cut-Off " . $this->end_date,
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

            DB::commit();

            Notification::make()->title('Kontrabon Konsinyasi Berhasil Dibuat')->success()->send();
            $this->calculateSellout();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Terjadi Kesalahan: ' . $e->getMessage())->danger()->send();
        }
    }
}
