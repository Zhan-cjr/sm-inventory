<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Carbon\Carbon;

class RekapitulasiTransaksi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?string $navigationLabel = 'Rekapitulasi Transaksi';
    protected static ?string $title = 'Rekapitulasi Transaksi';
    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Branch::query()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('penerimaan')
                    ->label('Penerimaan Brg')
                    ->money('IDR', true)
                    ->state(fn ($record, $livewire) => $this->computeMetric($record, $livewire, 'penerimaan'))
                    ->summarize(Summarizer::make()->using(fn (\Illuminate\Database\Query\Builder $query) => $this->computeTotalMetric($query, 'penerimaan'))->money('IDR', true)),
                TextColumn::make('retur_beli')
                    ->label('Retur Pembelian')
                    ->money('IDR', true)
                    ->state(fn ($record, $livewire) => $this->computeMetric($record, $livewire, 'retur_beli'))
                    ->summarize(Summarizer::make()->using(fn (\Illuminate\Database\Query\Builder $query) => $this->computeTotalMetric($query, 'retur_beli'))->money('IDR', true)),
                TextColumn::make('koreksi_retur')
                    ->label('Koreksi Stok (Retur)')
                    ->money('IDR', true)
                    ->state(fn ($record, $livewire) => $this->computeMetric($record, $livewire, 'koreksi_retur'))
                    ->summarize(Summarizer::make()->using(fn (\Illuminate\Database\Query\Builder $query) => $this->computeTotalMetric($query, 'koreksi_retur'))->money('IDR', true)),
                TextColumn::make('penjualan')
                    ->label('Penjualan Kasir')
                    ->money('IDR', true)
                    ->state(fn ($record, $livewire) => $this->computeMetric($record, $livewire, 'penjualan'))
                    ->summarize(Summarizer::make()->using(fn (\Illuminate\Database\Query\Builder $query) => $this->computeTotalMetric($query, 'penjualan'))->money('IDR', true)),
                TextColumn::make('retur_jual')
                    ->label('Retur Penjualan')
                    ->money('IDR', true)
                    ->state(fn ($record, $livewire) => $this->computeMetric($record, $livewire, 'retur_jual'))
                    ->summarize(Summarizer::make()->using(fn (\Illuminate\Database\Query\Builder $query) => $this->computeTotalMetric($query, 'retur_jual'))->money('IDR', true)),
                TextColumn::make('pengeluaran')
                    ->label('Pengeluaran')
                    ->money('IDR', true)
                    ->state(fn ($record, $livewire) => $this->computeMetric($record, $livewire, 'pengeluaran'))
                    ->summarize(Summarizer::make()->using(fn (\Illuminate\Database\Query\Builder $query) => $this->computeTotalMetric($query, 'pengeluaran'))->money('IDR', true)),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        \Filament\Forms\Components\Select::make('period')
                            ->label('Periode Tanggal')
                            ->options([
                                'today' => 'Hari Ini',
                                'yesterday' => 'Kemarin',
                                'this_week' => 'Minggu Ini',
                                'last_week' => 'Minggu Kemarin',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Kemarin',
                                'custom' => 'Custom Pilih Tanggal',
                            ])
                            ->default('this_month')
                            ->live(),
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal')
                            ->visible(fn ($get) => $get('period') === 'custom'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal')
                            ->visible(fn ($get) => $get('period') === 'custom'),
                    ])
                    ->query(function (Builder $query) {
                        return $query; // Do not apply date filter to Branch query
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['period'] ?? null) {
                            $labels = [
                                'today' => 'Hari Ini',
                                'yesterday' => 'Kemarin',
                                'this_week' => 'Minggu Ini',
                                'last_week' => 'Minggu Kemarin',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Kemarin',
                                'custom' => 'Custom Tanggal',
                            ];
                            
                            if ($data['period'] !== 'custom') {
                                $indicators[] = 'Periode: ' . ($labels[$data['period']] ?? '');
                            } else {
                                if ($data['created_from'] ?? null) {
                                    $indicators[] = 'Dari: ' . Carbon::parse($data['created_from'])->format('d M Y');
                                }
                                if ($data['created_until'] ?? null) {
                                    $indicators[] = 'Sampai: ' . Carbon::parse($data['created_until'])->format('d M Y');
                                }
                            }
                        }
                        return $indicators;
                    }),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(\App\Models\Branch::pluck('name', 'id'))
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'rekapitulasi_transaksi',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ])
            ->paginationPageOptions([10, 25, 50, 'all']);
    }

    private function getDatesFromLivewire($livewire)
    {
        $filters = $livewire->tableFilters ?? [];
        $dateData = $filters['transaction_date'] ?? null;
        
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        if ($dateData) {
            $period = $dateData['period'] ?? null;
            if ($period === 'today') {
                $start = Carbon::today();
                $end = Carbon::today()->endOfDay();
            } elseif ($period === 'yesterday') {
                $start = Carbon::yesterday();
                $end = Carbon::yesterday()->endOfDay();
            } elseif ($period === 'this_week') {
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
            } elseif ($period === 'last_week') {
                $start = Carbon::now()->subWeek()->startOfWeek();
                $end = Carbon::now()->subWeek()->endOfWeek();
            } elseif ($period === 'this_month') {
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
            } elseif ($period === 'last_month') {
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
            } elseif ($period === 'custom') {
                if (!empty($dateData['created_from'])) $start = Carbon::parse($dateData['created_from'])->startOfDay();
                if (!empty($dateData['created_until'])) $end = Carbon::parse($dateData['created_until'])->endOfDay();
            }
        }

        return [$start, $end];
    }

    private function computeMetric($branch, $livewire, $type)
    {
        [$startDateTime, $endDateTime] = $this->getDatesFromLivewire($livewire);
        return $this->calculateQuery($branch->id, $startDateTime, $endDateTime, $type);
    }

    private function computeTotalMetric($query, $type)
    {
        // $query represents the filtered branches query
        $branchIds = $query->pluck('id');
        [$startDateTime, $endDateTime] = $this->getDatesFromLivewire($this);

        return $this->calculateQuery($branchIds, $startDateTime, $endDateTime, $type);
    }

    private function calculateQuery($branchIds, $startDateTime, $endDateTime, $type)
    {
        $isMultiple = is_iterable($branchIds);

        if ($type === 'penerimaan') {
            $q = \App\Models\GoodsReceipt::whereIn('status', ['RECEIVED', 'COMPLETED', 'completed', 'approved'])
                ->whereBetween('receipt_date', [$startDateTime, $endDateTime]);
            if ($isMultiple) $q->whereIn('branch_id', $branchIds);
            else $q->where('branch_id', $branchIds);
            return (float) $q->sum('total_amount');
        }
        if ($type === 'retur_beli') {
            $q = \App\Models\PurchaseReturn::whereIn('status', ['completed', 'approved'])
                ->whereBetween('return_date', [$startDateTime, $endDateTime]);
            if ($isMultiple) $q->whereIn('branch_id', $branchIds);
            else $q->where('branch_id', $branchIds);
            return (float) $q->sum('total_amount');
        }
        if ($type === 'koreksi_retur') {
            $returReasonIds = \App\Models\AdjustmentReason::where('name', 'like', '%retur%')->pluck('id');
            $q = \App\Models\StockAdjustment::whereIn('status', ['COMPLETED', 'completed', 'APPROVED', 'approved'])
                ->whereIn('adjustment_reason_id', $returReasonIds)
                ->whereBetween('adjustment_date', [$startDateTime, $endDateTime]);
            if ($isMultiple) $q->whereIn('branch_id', $branchIds);
            else $q->where('branch_id', $branchIds);
            
            $koreksiReturIds = $q->pluck('id');
            if ($koreksiReturIds->isEmpty()) return 0;
            
            return (float) \App\Models\StockAdjustmentItem::whereIn('stock_adjustment_id', $koreksiReturIds)
                ->sum(\Illuminate\Support\Facades\DB::raw('ABS(adjustment_quantity * unit_cost)'));
        }
        if ($type === 'penjualan') {
            $q = \App\Models\Transaction::where('transaction_type', 'SALES')
                ->where('is_voided', false)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime]);
            if ($isMultiple) $q->whereIn('branch_id', $branchIds);
            else $q->where('branch_id', $branchIds);
            return (float) $q->sum('final_amount');
        }
        if ($type === 'retur_jual') {
            $q = \App\Models\Transaction::where('transaction_type', 'RETURN')
                ->where('is_voided', false)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime]);
            if ($isMultiple) $q->whereIn('branch_id', $branchIds);
            else $q->where('branch_id', $branchIds);
            return abs((float) $q->sum('final_amount'));
        }
        if ($type === 'pengeluaran') {
            $q = \App\Models\Expense::whereBetween('expense_date', [$startDateTime, $endDateTime]);
            if ($isMultiple) $q->whereIn('branch_id', $branchIds);
            else $q->where('branch_id', $branchIds);
            return (float) $q->sum('amount');
        }
        
        return 0;
    }
}
