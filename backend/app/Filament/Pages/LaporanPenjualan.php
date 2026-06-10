<?php

namespace App\Filament\Pages;

use App\Filament\Exports\TransactionExporter;
use App\Models\Transaction;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanPenjualan extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Laporan Penjualan';
    protected static ?string $title = 'Laporan Penjualan';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('is_voided', false)
                    ->with(['branch', 'cashier'])
            )
            ->columns([
                TextColumn::make('local_transaction_id')
                    ->label('No Transaksi')
                    ->searchable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('cashier.name')
                    ->label('Kasir'),
                TextColumn::make('payment_method')
                    ->label('Metode'),
                TextColumn::make('total_amount')
                    ->label('Total Awal')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR', true),
                TextColumn::make('final_amount')
                    ->label('Pendapatan Bersih')
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
                    ->sortable(),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('cashier_id')
                    ->label('Kasir')
                    ->relationship('cashier', 'name')
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-penjualan',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                ExportAction::make()
                    ->exporter(TransactionExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}






