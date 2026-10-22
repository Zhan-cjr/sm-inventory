<?php

namespace App\Filament\Pages;

use App\Models\GoodsReceiptItem;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\GoodsReceiptItemExporter;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Livewire\Attributes\Url;

class LaporanBarangDibeli extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    #[Url(as: 'tab')]
    public string $activeTab = 'detail';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Laporan Barang Dibeli';
    protected static ?string $title = 'Laporan Barang Dibeli (Penerimaan)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.report-page';

    public function getReportTabs(): array
    {
        return [
            'detail' => [
                'label' => 'Detail Per Transaksi',
                'icon' => 'heroicon-o-list-bullet',
            ],
            'rekap' => [
                'label' => 'Rekap Per Barang',
                'icon' => 'heroicon-o-squares-2x2',
            ],
        ];
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['id'] ?? $record['product_id'] ?? uniqid());
        }

        return (string) ($record->getKey() ?? $record->id ?? $record->product_id ?? uniqid());
    }

    public function table(Table $table): Table
    {
        $isRekap = $this->activeTab === 'rekap';

        return $table
            ->defaultKeySort(false)
            ->defaultSort($isRekap ? 'quantity_received' : 'goodsReceipt.receipt_date', 'desc')
            ->query(
                GoodsReceiptItem::query()
                    ->whereNotNull('goods_receipt_items.product_id')
                    ->when($isRekap, function ($q) {
                        $q->selectRaw("
                            goods_receipt_items.product_id as id,
                            goods_receipt_items.product_id,
                            SUM(goods_receipt_items.quantity_ordered) as quantity_ordered,
                            SUM(goods_receipt_items.quantity_received) as quantity_received,
                            SUM(goods_receipt_items.subtotal) as subtotal
                        ")
                        ->groupBy('goods_receipt_items.product_id');
                    })
                    ->when(Auth::user()->branch_id !== null, fn ($q) => $q->whereHas('goodsReceipt', fn ($gr) => $gr->where('branch_id', Auth::user()->branch_id)))
                    ->with(['goodsReceipt', 'goodsReceipt.branch', 'goodsReceipt.supplier', 'goodsReceipt.purchaseOrder', 'product', 'product.supplier'])
            )
            ->columns($isRekap ? [
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('quantity_ordered')
                    ->label('Total Qty PO')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('quantity_received')
                    ->label('Total Qty Diterima')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('avg_price')
                    ->label('Harga Rata-Rata')
                    ->money('IDR', true)
                    ->state(fn ($record) => $record->quantity_received > 0 ? ($record->subtotal / $record->quantity_received) : ($record->product?->cost_price ?? 0)),
                TextColumn::make('subtotal')
                    ->label('Total Pembelian (Net)')
                    ->money('IDR', true)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR', true))
                    ->sortable(),
            ] : [
                TextColumn::make('goodsReceipt.receipt_date')
                    ->label('Tanggal Terima')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('goodsReceipt.receipt_number')
                    ->label('No Penerimaan')
                    ->searchable(),
                TextColumn::make('goodsReceipt.purchaseOrder.po_number')
                    ->label('No PO')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('goodsReceipt.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('goodsReceipt.branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('quantity_ordered')
                    ->label('Qty PO')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('quantity_received')
                    ->label('Qty Diterima')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga Beli/Satuan')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('discount_1')
                    ->label('Diskon 1 (%)')
                    ->numeric(),
                TextColumn::make('subtotal')
                    ->label('Subtotal (Net)')
                    ->money('IDR', true)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR', true))
                    ->sortable(),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('goodsReceipt.receipt_date', 'receipt_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('goodsReceipt.branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('goodsReceipt.supplier', 'name'),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-barang-dibeli',
                        'tab' => $this->activeTab,
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(GoodsReceiptItemExporter::class)
                        ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xlsx (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-barang-dibeli',
                            'tab' => $this->activeTab,
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
            ]);
    }
}






