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

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanPersediaan extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Laporan Persediaan';
    protected static ?string $title = 'Laporan Persediaan (Valuasi Stok)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->with(['branch', 'product', 'product.category'])
                    ->addSelect([
                        'batch_valuation' => \App\Models\StockBatch::select(\Illuminate\Support\Facades\DB::raw('COALESCE(SUM(remaining_quantity * cost_price), 0)'))
                            ->whereColumn('product_id', 'stocks.product_id')
                            ->whereColumn('branch_id', 'stocks.branch_id')
                            ->where('remaining_quantity', '>', 0)
                    ])
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
                TextColumn::make('cost_price_tax')
                    ->label('Harga Pokok (Rata-rata Batch)')
                    ->money('IDR', true)
                    ->state(function (Stock $record) {
                        if ($record->quantity_on_hand > 0 && $record->batch_valuation > 0) {
                            return $record->batch_valuation / $record->quantity_on_hand;
                        }
                        return $record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0);
                    })
                    ->sortable(),
                TextColumn::make('valuation')
                    ->label('Valuasi Stok (FIFO)')
                    ->money('IDR', true)
                    ->state(function (Stock $record) {
                        if ($record->quantity_on_hand > 0 && $record->batch_valuation > 0) {
                            return (float) $record->batch_valuation;
                        }
                        return (float) $record->quantity_on_hand * ($record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0));
                    })
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
                \Filament\Actions\Action::make('cetak_rekap')
                    ->label('Cetak Rekap Total')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'rekap-total-stok',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                ExportAction::make()
                    ->exporter(StockExporter::class)
                    ->formats([
                        \Filament\Actions\Exports\Enums\ExportFormat::Csv,
                        \Filament\Actions\Exports\Enums\ExportFormat::Xlsx,
                    ])
                    ->label('Export CSV / Excel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->columnMapping(false)
            ]);
    }
}






