<?php

namespace App\Filament\Pages;

use App\Models\StockOpnameSession;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanStokOpnameRingkas extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Ringkas';
    protected static ?string $title = 'Laporan Stok Opname (Mode Ringkas)';
    protected static ?string $cluster = \App\Filament\Clusters\LaporanStokOpname\LaporanStokOpnameCluster::class;
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockOpnameSession::query()
                    ->with(['branch', 'creator', 'approver'])
            )
            ->columns([
                TextColumn::make('session_number')
                    ->label('No Sesi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('opname_date')
                    ->label('Tgl Opname')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'warning',
                        'COUNT1_DONE' => 'info',
                        'COUNT2_DONE' => 'info',
                        'COMPLETED' => 'success',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('progress_hitung')
                    ->label('Progress Hitung')
                    ->state(function (StockOpnameSession $record) {
                        $c1 = $record->count1_progress;
                        $c2 = $record->count2_progress;
                        return "H1: {$c1['done']}/{$c1['total']} | H2: {$c2['done']}/{$c2['total']}";
                    }),
                TextColumn::make('discrepancy_count')
                    ->label('Item Selisih')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->state(fn (StockOpnameSession $record) => $record->discrepancy_count),
                TextColumn::make('nominal_plus')
                    ->label('Total Nominal (+)')
                    ->money('IDR', true)
                    ->state(function (StockOpnameSession $record) {
                        $summary = $record->getProductSummary();
                        $totalNominal = 0;
                        
                        $stocks = [];
                        if ($record->branch_id) {
                            $productIds = $summary->pluck('product_id')->filter()->toArray();
                            if (!empty($productIds)) {
                                $stocks = \App\Models\Stock::where('branch_id', $record->branch_id)
                                            ->whereIn('product_id', $productIds)
                                            ->with('product')
                                            ->get()
                                            ->keyBy('product_id');
                            }
                        }

                        foreach($summary as $prodSummary) {
                            $diff = $prodSummary['final_disc'];
                            if ($diff <= 0) continue;
                            
                            $pid = $prodSummary['product_id'];
                            $price = 0;
                            if ($record->branch_id && isset($stocks[$pid])) {
                                $st = $stocks[$pid];
                                $price = $st->cost_price_tax > 0 ? $st->cost_price_tax : ($st->product->cost_price_tax ?? $st->product->cost_price ?? 0);
                            } else {
                                $p = \App\Models\Product::find($pid);
                                if ($p) {
                                    $price = $p->cost_price_tax ?? $p->cost_price ?? 0;
                                }
                            }
                            $totalNominal += ($diff * $price);
                        }
                        return $totalNominal > 0 ? $totalNominal : null;
                    }),
                TextColumn::make('nominal_minus')
                    ->label('Total Nominal (-)')
                    ->money('IDR', true)
                    ->state(function (StockOpnameSession $record) {
                        $summary = $record->getProductSummary();
                        $totalNominal = 0;
                        
                        $stocks = [];
                        if ($record->branch_id) {
                            $productIds = $summary->pluck('product_id')->filter()->toArray();
                            if (!empty($productIds)) {
                                $stocks = \App\Models\Stock::where('branch_id', $record->branch_id)
                                            ->whereIn('product_id', $productIds)
                                            ->with('product')
                                            ->get()
                                            ->keyBy('product_id');
                            }
                        }

                        foreach($summary as $prodSummary) {
                            $diff = $prodSummary['final_disc'];
                            if ($diff >= 0) continue;
                            
                            $pid = $prodSummary['product_id'];
                            $price = 0;
                            if ($record->branch_id && isset($stocks[$pid])) {
                                $st = $stocks[$pid];
                                $price = $st->cost_price_tax > 0 ? $st->cost_price_tax : ($st->product->cost_price_tax ?? $st->product->cost_price ?? 0);
                            } else {
                                $p = \App\Models\Product::find($pid);
                                if ($p) {
                                    $price = $p->cost_price_tax ?? $p->cost_price ?? 0;
                                }
                            }
                            $totalNominal += (abs($diff) * $price);
                        }
                        return $totalNominal > 0 ? $totalNominal : null;
                    }),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('opname_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('status')
                    ->options([
                        'OPEN' => 'Open',
                        'COUNT1_DONE' => 'Hitung 1 Selesai',
                        'COUNT2_DONE' => 'Hitung 2 Selesai',
                        'COMPLETED' => 'Selesai',
                        'CANCELLED' => 'Dibatalkan',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-stok-opname-ringkas',
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\StockOpnameSessionExporter::class)
                    ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx, \Filament\Actions\Exports\Enums\ExportFormat::Csv])
                    ->label('Export')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ])
            ->defaultSort('created_at', 'desc');
    }
}
