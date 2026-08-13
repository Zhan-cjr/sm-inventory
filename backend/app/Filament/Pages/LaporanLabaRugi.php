<?php

namespace App\Filament\Pages;

use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\LabaRugiExporter;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanLabaRugi extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Laporan Laba Rugi';
    protected static ?string $title = 'Laporan Laba Rugi (Kotor)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 6;

    public function getSubheading(): ?string
    {
        return 'Catatan: Nilai HPP yang tercantum pada laporan ini sudah termasuk PPN (HPP + PPN).';
    }

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        $subquery = \Illuminate\Support\Facades\DB::table('transactions as t')
            ->select([
                't.id',
                't.local_transaction_id',
                't.transaction_date',
                't.branch_id',
                't.final_amount',
                't.payment_method',
                't.payment_details',
                \Illuminate\Support\Facades\DB::raw("'OFFLINE' as transaction_source"),
                \Illuminate\Support\Facades\DB::raw("COALESCE((
                    SELECT SUM(
                        COALESCE(
                            (SELECT SUM(sbd.quantity * sb.cost_price) 
                             FROM stock_batch_deductions sbd 
                             JOIN stock_batches sb ON sbd.stock_batch_id = sb.id 
                             WHERE sbd.transaction_item_id = ti.id),
                            ti.quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0)
                        )
                    )
                    FROM transaction_items ti
                    JOIN products p ON ti.product_id = p.id
                    LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
                    WHERE ti.transaction_id = t.id
                ), 0) as raw_cogs")
            ])
            ->where('t.is_voided', false)
            ->unionAll(
                \Illuminate\Support\Facades\DB::table('ecommerce_orders as eo')
                    ->select([
                        'eo.id',
                        'eo.id as local_transaction_id',
                        'eo.created_at as transaction_date',
                        'eo.branch_id',
                        'eo.total_amount as final_amount',
                        'eo.payment_method',
                        \Illuminate\Support\Facades\DB::raw("NULL as payment_details"),
                        \Illuminate\Support\Facades\DB::raw("'ONLINE' as transaction_source"),
                        \Illuminate\Support\Facades\DB::raw("COALESCE((
                            SELECT SUM(
                                COALESCE(
                                    (SELECT SUM(sbd.quantity * sb.cost_price) 
                                     FROM stock_batch_deductions sbd 
                                     JOIN stock_batches sb ON sbd.stock_batch_id = sb.id 
                                     WHERE sbd.ecommerce_order_item_id = ei.id),
                                    ei.quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0)
                                )
                            )
                            FROM ecommerce_order_items ei
                            JOIN products p ON ei.product_id = p.id
                            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = eo.branch_id
                            WHERE ei.ecommerce_order_id = eo.id
                        ), 0) as raw_cogs")
                    ])
                    ->where('eo.status', 'COMPLETED')
            );

        return $table
            ->query(
                Transaction::query()
                    ->fromSub($subquery, 'transactions')
                    ->with(['branch']) // Items are no longer eager-loaded because it'll fail for ecommerce IDs
            )
            ->columns([
                TextColumn::make('local_transaction_id')
                    ->label('No Transaksi')
                    ->searchable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('final_amount')
                    ->label('Penjualan Bersih')
                    ->state(function (Transaction $record) {
                        $pointPayment = 0.0;
                        if (!empty($record->payment_details)) {
                            $details = $record->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                            }
                        } elseif (strtoupper($record->payment_method) === 'POINT') {
                            $pointPayment = (float) $record->final_amount;
                        }
                        return $record->final_amount - $pointPayment;
                    })
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Penjualan')
                            ->using(function ($query) {
                                $totalFinalAmount = (clone $query)->sum('final_amount');
                                
                                $pointOnly = (clone $query)->whereIn('payment_method', ['POINT', 'point'])->sum('final_amount');
                                $multiRecords = (clone $query)->whereIn('payment_method', ['MULTI', 'multi'])->get(['payment_details']);
                                $multiPoint = $multiRecords->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return is_array($details) ? collect($details)->where('method', 'POINT')->sum('amount') : 0;
                                });
                                return $totalFinalAmount - ($pointOnly + $multiPoint);
                            })
                            ->money('IDR')
                    )
                    ->sortable(),
                TextColumn::make('cogs')
                    ->label('Total HPP')
                    ->money('IDR', true)
                    ->state(fn (Transaction $record): float => $record->raw_cogs)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total HPP')
                            ->using(fn ($query) => (clone $query)->sum('raw_cogs'))
                            ->money('IDR')
                    )
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('raw_cogs', $direction)),
                TextColumn::make('gross_profit')
                    ->label('Laba Kotor')
                    ->money('IDR', true)
                    ->state(function (Transaction $record) {
                        $pointPayment = 0.0;
                        if (!empty($record->payment_details)) {
                            $details = $record->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                            }
                        } elseif (strtoupper($record->payment_method) === 'POINT') {
                            $pointPayment = (float) $record->final_amount;
                        }
                        return ($record->final_amount - $pointPayment) - $record->raw_cogs;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Laba Kotor')
                            ->using(function ($query) {
                                $totalFinalAmount = (clone $query)->sum('final_amount');
                                $pointOnly = (clone $query)->whereIn('payment_method', ['POINT', 'point'])->sum('final_amount');
                                $multiRecords = (clone $query)->whereIn('payment_method', ['MULTI', 'multi'])->get(['payment_details']);
                                $multiPoint = $multiRecords->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return is_array($details) ? collect($details)->where('method', 'POINT')->sum('amount') : 0;
                                });
                                $netSales = $totalFinalAmount - ($pointOnly + $multiPoint);
                                $totalCogs = (clone $query)->sum('raw_cogs');
                                return $netSales - $totalCogs;
                            })
                            ->money('IDR')
                    )
                    ->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw('(final_amount - raw_cogs) ' . $direction)),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('transaction_source')
                    ->label('Sumber Penjualan')
                    ->options([
                        'OFFLINE' => 'Kasir Offline',
                        'ONLINE' => 'Online E-Commerce',
                    ]),
                \App\Filament\Filters\DateFilterHelper::make('transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-laba-rugi',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(LabaRugiExporter::class)
                        ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-laba-rugi',
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






