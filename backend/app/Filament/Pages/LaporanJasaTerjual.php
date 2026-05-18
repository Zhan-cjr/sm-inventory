<?php

namespace App\Filament\Pages;

use App\Models\TransactionItem;
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
use App\Filament\Exports\TransactionItemExporter;
use Illuminate\Support\Facades\Auth;

class LaporanJasaTerjual extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Laporan Jasa Terjual';
    protected static ?string $title = 'Laporan Jasa Terjual';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TransactionItem::query()
                    ->whereHas('transaction', fn (Builder $query) => $query->where('is_voided', false))
                    ->whereNotNull('service_id')
                    ->with(['transaction', 'transaction.branch', 'service'])
            )
            ->columns([
                TextColumn::make('transaction.transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('transaction.local_transaction_id')
                    ->label('No Transaksi')
                    ->searchable(),
                TextColumn::make('transaction.branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('service.code')
                    ->label('Kode Jasa')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Nama Jasa')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('discount_per_item')
                    ->label('Diskon/Item')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal (Net)')
                    ->money('IDR', true)
                    ->state(fn (TransactionItem $record): float => ($record->unit_price - $record->discount_per_item) * $record->quantity)
                    ->sortable(),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('transaction.transaction_date', 'transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('transaction.branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-jasa-terjual',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                ExportAction::make()
                    ->exporter(TransactionItemExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}






