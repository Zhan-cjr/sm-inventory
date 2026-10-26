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
                    ->select('stocks.*')
                    ->selectRaw('GREATEST(0, stocks.quantity_on_hand - COALESCE(stocks.quantity_damaged, 0)) as salable_quantity')
                    ->whereHas('product', fn ($q) => $q->where(fn ($sub) => $sub->where('product_type', '!=', 'digital')->orWhereNull('product_type')))
                    ->when(Auth::user()->branch_id !== null, fn ($q) => $q->where('branch_id', Auth::user()->branch_id))
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
                TextColumn::make('salable_quantity')
                    ->label('Stok Layak Jual')
                    ->state(fn (Stock $record) => $record->salable_quantity)
                    ->numeric(decimalPlaces: 2)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, Stock $record) => $state <= ($record->reorder_point ?? 0) ? 'danger' : 'success'),
                TextColumn::make('quantity_damaged')
                    ->label('Stok BS (Karantina)')
                    ->numeric(decimalPlaces: 2)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => (float)$state > 0 ? 'warning' : 'gray')
                    ->tooltip(fn ($state) => (float)$state > 0 ? "Ada {$state} pcs barang rusak di karantina siap retur" : null),
                TextColumn::make('quantity_on_hand')
                    ->label('Total Fisik Toko')
                    ->numeric(decimalPlaces: 2)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label('Valuasi Layak Jual (FIFO)')
                    ->money('IDR', true)
                    ->state(function (Stock $record) {
                        $unitCost = ($record->quantity_on_hand > 0 && $record->batch_valuation > 0)
                            ? ($record->batch_valuation / $record->quantity_on_hand)
                            : ($record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0));
                        return (float)$record->salable_quantity * (float)$unitCost;
                    })
                    ->sortable(),
                TextColumn::make('damaged_valuation')
                    ->label('Valuasi BS / Siap Retur')
                    ->money('IDR', true)
                    ->state(function (Stock $record) {
                        $unitCost = ($record->quantity_on_hand > 0 && $record->batch_valuation > 0)
                            ? ($record->batch_valuation / $record->quantity_on_hand)
                            : ($record->cost_price_tax > 0 ? $record->cost_price_tax : ($record->product->cost_price_tax ?? $record->product->cost_price ?? 0));
                        return (float)($record->quantity_damaged ?? 0) * (float)$unitCost;
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('product.supplier', 'name', fn ($query) => $query->where('is_active', true)->orderBy('name'))
                    ->searchable(),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('product.category', 'name', fn ($query) => $query->where('is_active', true)
                        ->whereHas('products', fn ($p) => $p->where(fn ($sub) => $sub->where('product_type', '!=', 'digital')->orWhereNull('product_type')))
                        ->orderBy('name')
                    ),
                SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'available' => 'Hanya Stok Layak Jual (> 0)',
                        'has_bs' => '⚠️ Hanya Barang BS / Karantina (Siap Retur)',
                        'empty' => 'Hanya Stok Kosong (<= 0)',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? null) === 'available') {
                            return $query->whereRaw('GREATEST(0, stocks.quantity_on_hand - COALESCE(stocks.quantity_damaged, 0)) > 0');
                        }
                        if (($data['value'] ?? null) === 'has_bs') {
                            return $query->where('stocks.quantity_damaged', '>', 0);
                        }
                        if (($data['value'] ?? null) === 'empty') {
                            return $query->whereRaw('GREATEST(0, stocks.quantity_on_hand - COALESCE(stocks.quantity_damaged, 0)) <= 0');
                        }
                        return $query;
                    }),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-persediaan',
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\Action::make('cetak_rekap')
                    ->label('Cetak Rekap Stok (Layak Jual + BS)')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'rekap-total-stok',
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(\App\Filament\Exports\StockExporter::class)
                        ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xlsx (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-persediaan',
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






