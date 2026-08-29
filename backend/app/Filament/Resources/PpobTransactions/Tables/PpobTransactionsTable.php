<?php

namespace App\Filament\Resources\PpobTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PpobTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('transaction.receipt_number')
                    ->label('No. Struk')
                    ->searchable(),
                TextColumn::make('ref_id')
                    ->label('Ref ID')
                    ->searchable(),
                TextColumn::make('customer_no')
                    ->label('No. Tujuan')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Nama Customer')
                    ->searchable(),
                TextColumn::make('buyer_sku_code')
                    ->label('SKU Provider')
                    ->searchable(),
                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'digiflazz' => 'primary',
                        'ama' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Harga Modal')
                    ->money('IDR', locale: 'id')
                    ->state(function ($record) {
                        return strtolower($record->status ?? '') === 'gagal' ? 0 : $record->price;
                    })
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR', locale: 'id'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sukses' => 'success',
                        'Pending' => 'warning',
                        'Gagal' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('sn')
                    ->label('SN / Token')
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('transaction.branch', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user() && !auth()->user()->branch_id),
                \Filament\Tables\Filters\SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'digiflazz' => 'Digiflazz',
                        'ama' => 'AMA',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Sukses' => 'Sukses',
                        'Pending' => 'Pending',
                        'Gagal' => 'Gagal',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-ppob',
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null
                    ]), true)
                    ->color('primary')
                    ->button(),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make('export_ppob')
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(\App\Filament\Exports\PpobTransactionExporter::class)
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->modalHeading('Pilih Kolom Export')
                        ->modalSubmitActionLabel('Proses Export'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-ppob',
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
