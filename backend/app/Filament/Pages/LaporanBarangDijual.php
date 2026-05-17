<?php

namespace App\Filament\Pages;

use App\Models\TransactionItem;
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
use App\Filament\Exports\TransactionItemExporter;
use Illuminate\Support\Facades\Auth;

class LaporanBarangDijual extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Laporan Barang Dijual';
    protected static ?string $title = 'Laporan Barang Dijual (Item Terjual)';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TransactionItem::query()
                    ->whereHas('transaction', fn (Builder $query) => $query->where('is_voided', false))
                    ->with(['transaction', 'transaction.branch', 'product'])
            )
            ->columns([
                TextColumn::make('transaction.transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('transaction.local_transaction_id')
                    ->label('No Transaksi')
                    ->searchable(),
                TextColumn::make('transaction.branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('discount_per_item')
                    ->label('Diskon/Item')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal (Net)')
                    ->money('IDR', true)
                    ->state(fn (TransactionItem $record): float => ($record->unit_price - $record->discount_per_item) * $record->quantity)
                    ->sortable(),
            ])
            ->filters([
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereHas('transaction', function (Builder $query) use ($data) {
                            $query
                                ->when(
                                    $data['created_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                                );
                        });
                    }),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('transaction.branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransactionItemExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}
