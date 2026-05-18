<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\StockExporter;
use Illuminate\Support\Facades\Auth;

class LaporanPersediaan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Laporan Persediaan';
    protected static ?string $title = 'Laporan Persediaan (Valuasi Stok)';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->with(['branch', 'product', 'product.category'])
            )
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('product.category.name')
                    ->label('Kategori')
                    ->searchable(),
                TextColumn::make('quantity_on_hand')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, Stock $record) => $state <= ($record->reorder_point ?? 0) ? 'danger' : 'success'),
                TextColumn::make('product.cost_price')
                    ->label('Harga Pokok')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('valuation')
                    ->label('Valuasi Stok')
                    ->money('IDR', true)
                    ->state(fn (Stock $record): float => $record->quantity_on_hand * ($record->product->cost_price ?? 0))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('product.category', 'name'),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-persediaan',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                ExportAction::make()
                    ->exporter(StockExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}






