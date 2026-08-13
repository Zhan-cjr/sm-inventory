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

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanJasaTerjual extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Laporan Jasa Terjual';
    protected static ?string $title = 'Laporan Jasa Terjual';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 3;

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
                TextColumn::make('transaction_no')
                    ->label('No Transaksi')
                    ->state(fn (TransactionItem $record): string =>
                        !empty($record->transaction?->receipt_number)
                            ? $record->transaction->receipt_number
                            : (!empty($record->transaction?->local_transaction_id)
                                ? $record->transaction->local_transaction_id
                                : strtoupper(substr($record->transaction_id ?? '', 0, 8)))
                    )
                    ->badge()
                    ->color('gray')
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('transaction', fn ($q) =>
                            $q->where('receipt_number', 'like', "%{$search}%")
                              ->orWhere('local_transaction_id', 'like', "%{$search}%")
                        )
                    ),
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
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
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
                    ->state(fn (\App\Models\TransactionItem $record): float => ($record->unit_price - $record->discount_per_item) * $record->quantity)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Summarizer::make()->using(function (\Illuminate\Database\Query\Builder $query) {
                        return (float) $query->sum(\Illuminate\Support\Facades\DB::raw('(unit_price - discount_per_item) * quantity'));
                    })->money('IDR', true))
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
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(\App\Filament\Exports\TransactionItemExporter::class)
                        ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-jasa-terjual',
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






