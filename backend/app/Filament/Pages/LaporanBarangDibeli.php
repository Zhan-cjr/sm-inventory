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

class LaporanBarangDibeli extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Laporan Barang Dibeli';
    protected static ?string $title = 'Laporan Barang Dibeli (Penerimaan)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GoodsReceiptItem::query()
                    ->with(['goodsReceipt', 'goodsReceipt.branch', 'goodsReceipt.supplier', 'product'])
            )
            ->columns([
                TextColumn::make('goodsReceipt.receipt_date')
                    ->label('Tanggal Terima')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('goodsReceipt.receipt_number')
                    ->label('No Penerimaan')
                    ->searchable(),
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
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                ExportAction::make()
                    ->exporter(GoodsReceiptItemExporter::class)
                    ->formats([
                        \Filament\Actions\Exports\Enums\ExportFormat::Csv,
                        \Filament\Actions\Exports\Enums\ExportFormat::Xlsx,
                    ])
                    ->label('Export CSV / Excel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}






