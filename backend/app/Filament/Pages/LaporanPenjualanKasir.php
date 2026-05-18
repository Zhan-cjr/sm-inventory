<?php

namespace App\Filament\Pages;

use App\Filament\Exports\TransactionExporter;
use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Auth;

class LaporanPenjualanKasir extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Penjualan Per Kassa';
    protected static ?string $title = 'Laporan Penjualan (Per Kassa)';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('is_voided', false)
                    ->with(['branch', 'cashier', 'terminal'])
            )
            ->defaultGroup('cashier.name')
            ->groups([
                Group::make('cashier.name')
                    ->label('Kasir')
                    ->collapsible(),
                Group::make('terminal.name')
                    ->label('Kassa / Terminal')
                    ->collapsible(),
                Group::make('branch.name')
                    ->label('Cabang')
                    ->collapsible(),
            ])
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
                TextColumn::make('terminal.name')
                    ->label('Kassa / Terminal'),
                TextColumn::make('cashier.name')
                    ->label('Kasir'),
                TextColumn::make('final_amount')
                    ->label('Pendapatan Bersih')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->money('IDR', true)->label('Total'))
                    ->sortable(),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('terminal_id')
                    ->label('Terminal/Kassa')
                    ->relationship('terminal', 'name'),
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
                        'type' => 'laporan-penjualan-kasir',
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




