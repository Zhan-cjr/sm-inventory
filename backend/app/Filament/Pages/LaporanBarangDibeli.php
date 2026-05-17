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

class LaporanBarangDibeli extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Laporan Barang Dibeli';
    protected static ?string $title = 'Laporan Barang Dibeli (Penerimaan)';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

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
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('quantity_received')
                    ->label('Qty Diterima')
                    ->numeric()
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
                    ->sortable(),
            ])
            ->filters([
                Filter::make('receipt_date')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereHas('goodsReceipt', function (Builder $query) use ($data) {
                            $query
                                ->when(
                                    $data['created_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '<=', $date),
                                );
                        });
                    }),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('goodsReceipt.branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('goodsReceipt.supplier', 'name'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(GoodsReceiptItemExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}
