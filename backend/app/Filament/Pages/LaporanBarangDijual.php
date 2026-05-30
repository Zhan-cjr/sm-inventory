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

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanBarangDijual extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Laporan Barang Dijual';
    protected static ?string $title = 'Laporan Barang Dijual';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TransactionItem::query()
                    ->whereHas('transaction', function (Builder $query) {
                        $query->where('is_voided', false);
                        if (Auth::user()->branch_id !== null) {
                            $query->where('branch_id', Auth::user()->branch_id);
                        }
                    })
                    ->whereNotNull('product_id')
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
                TextColumn::make('qty_terjual')
                    ->label('Qty Terjual')
                    ->state(fn (TransactionItem $record) => $record->quantity > 0 ? $record->quantity : 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty_retur')
                    ->label('Qty Retur')
                    ->state(fn (TransactionItem $record) => $record->quantity < 0 ? abs($record->quantity) : 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->label('Harga Beli + PPN')
                    ->money('IDR', true)
                    ->state(function (TransactionItem $record) {
                        $branch_id = $record->transaction?->branch_id;
                        $stock = \App\Models\Stock::where('product_id', $record->product_id)
                                ->where('branch_id', $branch_id)
                                ->first();
                                
                        if ($stock) {
                            return $stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price ?? 0);
                        }
                        
                        return $record->product?->cost_price_tax > 0 ? $record->product?->cost_price_tax : ($record->product?->cost_price ?? 0);
                    })
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga Jual')
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
                \App\Filament\Filters\DateFilterHelper::make('transaction.transaction_date', 'transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('transaction.branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-barang-dijual',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                ExportAction::make()
                    ->exporter(TransactionItemExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}






