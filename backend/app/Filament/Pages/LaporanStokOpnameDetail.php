<?php

namespace App\Filament\Pages;

use App\Models\StockOpnameItem;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanStokOpnameDetail extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'Detail';
    protected static ?string $title = 'Laporan Stok Opname (Mode Detail)';
    protected static ?string $cluster = \App\Filament\Clusters\LaporanStokOpname\LaporanStokOpnameCluster::class;
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockOpnameItem::query()
                    ->with(['session.branch', 'product', 'rackSession.rack'])
            )
            ->columns([
                TextColumn::make('session.session_number')
                    ->label('No Sesi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session.branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('rackSession.rack.rack_code')
                    ->label('Rak')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('system_quantity')
                    ->label('Stok Sistem')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->numeric(),
                TextColumn::make('count1_quantity')
                    ->label('Hitung 1')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->numeric(),
                TextColumn::make('count2_quantity')
                    ->label('Hitung 2')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->numeric(),
                TextColumn::make('final_quantity')
                    ->label('Hasil Akhir')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->numeric()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'gray',
                        'COUNTED' => 'info',
                        'MATCHED' => 'success',
                        'DISCREPANCY' => 'danger',
                        'FINAL_DONE' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('selisih_plus')
                    ->label('Selisih (+)')
                    ->state(function (StockOpnameItem $record) {
                        $sys = (float)$record->system_quantity;
                        $c1 = $record->count1_quantity !== null ? (float)$record->count1_quantity : null;
                        $c2 = $record->count2_quantity !== null ? (float)$record->count2_quantity : null;
                        $fin = $record->final_quantity !== null ? (float)$record->final_quantity : null;
                        
                        $qty = $fin ?? $c2 ?? $c1;
                        if ($qty === null) return '-';
                        $diff = $qty - $sys;
                        return $diff > 0 ? $diff : '-';
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('nominal_plus')
                    ->label('Nominal (+)')
                    ->money('IDR', true)
                    ->state(function (StockOpnameItem $record) {
                        $sys = (float)$record->system_quantity;
                        $c1 = $record->count1_quantity !== null ? (float)$record->count1_quantity : null;
                        $c2 = $record->count2_quantity !== null ? (float)$record->count2_quantity : null;
                        $fin = $record->final_quantity !== null ? (float)$record->final_quantity : null;
                        
                        $qty = $fin ?? $c2 ?? $c1;
                        if ($qty === null) return null;
                        $diff = $qty - $sys;
                        if ($diff <= 0) return null;

                        $price = 0;
                        if ($record->session && $record->session->branch_id) {
                            $stock = \App\Models\Stock::where('product_id', $record->product_id)
                                        ->where('branch_id', $record->session->branch_id)->first();
                            if ($stock) {
                                $price = $stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->product->cost_price_tax ?? $stock->product->cost_price ?? 0);
                            } else {
                                $price = $record->product->cost_price_tax ?? $record->product->cost_price ?? 0;
                            }
                        } else {
                            $price = $record->product->cost_price_tax ?? $record->product->cost_price ?? 0;
                        }
                        return $diff * $price;
                    }),
                TextColumn::make('selisih_minus')
                    ->label('Selisih (-)')
                    ->state(function (StockOpnameItem $record) {
                        $sys = (float)$record->system_quantity;
                        $c1 = $record->count1_quantity !== null ? (float)$record->count1_quantity : null;
                        $c2 = $record->count2_quantity !== null ? (float)$record->count2_quantity : null;
                        $fin = $record->final_quantity !== null ? (float)$record->final_quantity : null;
                        
                        $qty = $fin ?? $c2 ?? $c1;
                        if ($qty === null) return '-';
                        $diff = $qty - $sys;
                        return $diff < 0 ? abs($diff) : '-';
                    })
                    ->badge()
                    ->color('danger'),
                TextColumn::make('nominal_minus')
                    ->label('Nominal (-)')
                    ->money('IDR', true)
                    ->state(function (StockOpnameItem $record) {
                        $sys = (float)$record->system_quantity;
                        $c1 = $record->count1_quantity !== null ? (float)$record->count1_quantity : null;
                        $c2 = $record->count2_quantity !== null ? (float)$record->count2_quantity : null;
                        $fin = $record->final_quantity !== null ? (float)$record->final_quantity : null;
                        
                        $qty = $fin ?? $c2 ?? $c1;
                        if ($qty === null) return null;
                        $diff = $qty - $sys;
                        if ($diff >= 0) return null;

                        $price = 0;
                        if ($record->session && $record->session->branch_id) {
                            $stock = \App\Models\Stock::where('product_id', $record->product_id)
                                        ->where('branch_id', $record->session->branch_id)->first();
                            if ($stock) {
                                $price = $stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->product->cost_price_tax ?? $stock->product->cost_price ?? 0);
                            } else {
                                $price = $record->product->cost_price_tax ?? $record->product->cost_price ?? 0;
                            }
                        } else {
                            $price = $record->product->cost_price_tax ?? $record->product->cost_price ?? 0;
                        }
                        return abs($diff) * $price;
                    }),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('session.opname_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('session.branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'COUNTED' => 'Hitung Selesai',
                        'MATCHED' => 'Cocok',
                        'DISCREPANCY' => 'Selisih',
                        'FINAL_DONE' => 'Disetujui',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-stok-opname-detail',
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(\App\Filament\Exports\StockOpnameItemExporter::class)
                        ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-stok-opname-detail',
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button()
            ])
            ->defaultSort('created_at', 'desc');
    }
}
