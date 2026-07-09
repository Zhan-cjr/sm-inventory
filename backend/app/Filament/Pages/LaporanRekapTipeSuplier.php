<?php

namespace App\Filament\Pages;

use App\Models\TransactionItem;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;

class RekapSuplierTipe extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'transaction_items';
    protected $primaryKey = 'tipe_suplier';
    protected $keyType = 'string';
}

class LaporanRekapTipeSuplier extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Rekap Tipe Suplier';
    protected static ?string $title = 'Laporan Rekap Transaksi Per Tipe Suplier';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 16;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        $subquery = DB::table('transaction_items as ti')
            ->join('products as p', 'ti.product_id', '=', 'p.id')
            ->leftJoin('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->where('t.is_voided', false)
            ->selectRaw("
                CASE 
                    WHEN s.id IS NULL THEN 'TANPA SUPLIER'
                    WHEN s.name LIKE '[%]%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, ']', 1), '[', -1)
                    WHEN s.name LIKE '(%)%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, ')', 1), '(', -1)
                    WHEN s.name LIKE '{%}%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, '}', 1), '{', -1)
                    ELSE SUBSTRING_INDEX(s.name, ' ', 1)
                END as tipe_suplier,
                t.transaction_date,
                t.branch_id,
                CASE WHEN ti.quantity > 0 THEN ti.quantity * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END as jual,
                CASE WHEN ti.quantity > 0 THEN ti.quantity * COALESCE(NULLIF(p.cost_price_tax, 0), p.cost_price, 0) ELSE 0 END as hpp,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END as retur,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * COALESCE(NULLIF(p.cost_price_tax, 0), p.cost_price, 0) ELSE 0 END as hpp_retur
            ");

        return $table
            ->query(
                RekapSuplierTipe::query()
                    ->fromSub($subquery, 'transaction_items')
                    ->selectRaw("
                        tipe_suplier,
                        SUM(jual) as jual,
                        SUM(hpp) as hpp,
                        SUM(retur) as retur,
                        SUM(hpp_retur) as hpp_retur
                    ")
                    ->groupBy('tipe_suplier')
            )
            ->columns([
                TextColumn::make('tipe_suplier')
                    ->label('Tipe')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where('tipe_suplier', 'like', "%{$search}%");
                    })
                    ->sortable(),
                TextColumn::make('jual')
                    ->label('Jual')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->money('IDR', true))
                    ->sortable(),
                TextColumn::make('hpp')
                    ->label('HPP')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->money('IDR', true))
                    ->sortable(),
                TextColumn::make('retur')
                    ->label('Retur')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->money('IDR', true))
                    ->sortable(),
                TextColumn::make('hpp_retur')
                    ->label('HPP Retur')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->money('IDR', true))
                    ->sortable(),
                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->money('IDR', true)
                    ->state(function ($record) {
                        return ($record->jual - $record->hpp) - ($record->retur - $record->hpp_retur);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Selisih')
                            ->using(function ($query) {
                                $totalJual = (clone $query)->sum('jual');
                                $totalHpp = (clone $query)->sum('hpp');
                                $totalRetur = (clone $query)->sum('retur');
                                $totalHppRetur = (clone $query)->sum('hpp_retur');
                                return ($totalJual - $totalHpp) - ($totalRetur - $totalHppRetur);
                            })
                            ->money('IDR', true)
                    )
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('transaction_date'),
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::pluck('name', 'id'))
                    ->hidden(fn () => Auth::user()->branch_id !== null)
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value']) && $data['value']) {
                            $query->where('transaction_items.branch_id', $data['value']);
                        }
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-rekap-tipe-suplier',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ])
            ->actions([
                \Filament\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'Detail Barang: ' . $record->tipe_suplier)
                    ->modalContent(function ($record, \Filament\Tables\Contracts\HasTable $livewire) {
                        return view('filament.pages.detail-tipe-suplier-modal', [
                            'data' => (new static)->getDetailData($record->tipe_suplier, $livewire->tableFilters),
                            'tipe_suplier' => $record->tipe_suplier,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
            ])
            ->defaultSort('tipe_suplier')
            ->paginationPageOptions([10, 25, 50, 'all']);
    }

    public function getDetailData($tipeSuplier, $filters)
    {
        $branchId = $filters['branch_id']['value'] ?? null;
        $dateFilter = $filters['transaction_date'] ?? [];
        $period = $dateFilter['period'] ?? null;

        $subquery = DB::table('transaction_items as ti')
            ->join('products as p', 'ti.product_id', '=', 'p.id')
            ->leftJoin('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->where('t.is_voided', false)
            ->selectRaw("
                CASE 
                    WHEN s.id IS NULL THEN 'TANPA SUPLIER'
                    WHEN s.name LIKE '[%]%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, ']', 1), '[', -1)
                    WHEN s.name LIKE '(%)%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, ')', 1), '(', -1)
                    WHEN s.name LIKE '{%}%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, '}', 1), '{', -1)
                    ELSE SUBSTRING_INDEX(s.name, ' ', 1)
                END as tipe_suplier,
                t.transaction_date,
                t.branch_id,
                p.sku,
                p.barcode,
                p.name as product_name,
                CASE WHEN ti.quantity > 0 THEN ti.quantity ELSE 0 END as qty_jual,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) ELSE 0 END as qty_retur,
                CASE WHEN ti.quantity > 0 THEN ti.quantity * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END as jual,
                CASE WHEN ti.quantity > 0 THEN ti.quantity * COALESCE(NULLIF(p.cost_price_tax, 0), p.cost_price, 0) ELSE 0 END as hpp,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END as retur,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * COALESCE(NULLIF(p.cost_price_tax, 0), p.cost_price, 0) ELSE 0 END as hpp_retur
            ");

        $query = DB::table(DB::raw("({$subquery->toSql()}) as sub"))
            ->mergeBindings($subquery)
            ->where('tipe_suplier', $tipeSuplier)
            ->selectRaw("
                sku, barcode, product_name,
                SUM(qty_jual) as qty_jual,
                SUM(qty_retur) as qty_retur,
                SUM(jual) as jual,
                SUM(hpp) as hpp,
                SUM(retur) as retur,
                SUM(hpp_retur) as hpp_retur
            ")
            ->groupBy('sku', 'barcode', 'product_name')
            ->havingRaw('SUM(qty_jual) > 0 OR SUM(qty_retur) > 0');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($period === 'today') {
            $query->whereDate('transaction_date', \Carbon\Carbon::today());
        } elseif ($period === 'yesterday') {
            $query->whereDate('transaction_date', \Carbon\Carbon::yesterday());
        } elseif ($period === 'this_week') {
            $query->whereBetween('transaction_date', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()]);
        } elseif ($period === 'last_week') {
            $query->whereBetween('transaction_date', [\Carbon\Carbon::now()->subWeek()->startOfWeek(), \Carbon\Carbon::now()->subWeek()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $query->whereMonth('transaction_date', \Carbon\Carbon::now()->month)->whereYear('transaction_date', \Carbon\Carbon::now()->year);
        } elseif ($period === 'last_month') {
            $query->whereMonth('transaction_date', \Carbon\Carbon::now()->subMonth()->month)->whereYear('transaction_date', \Carbon\Carbon::now()->subMonth()->year);
        } elseif ($period === 'custom') {
            if (!empty($dateFilter['created_from'])) {
                $query->whereDate('transaction_date', '>=', $dateFilter['created_from']);
            }
            if (!empty($dateFilter['created_until'])) {
                $query->whereDate('transaction_date', '<=', $dateFilter['created_until']);
            }
        }

        return $query->orderBy('product_name')->get();
    }
}
