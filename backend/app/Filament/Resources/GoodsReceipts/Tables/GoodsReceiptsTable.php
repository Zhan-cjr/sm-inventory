<?php

namespace App\Filament\Resources\GoodsReceipts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Filters\DateFilterHelper;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class GoodsReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('No. Terima')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('receipt_date')
                    ->label('Tgl Terima')
                    ->date()
                    ->sortable(),
                TextColumn::make('purchaseOrder.po_number')
                    ->label('No. PO')
                    ->placeholder('- Tanpa PO -')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Pemasok')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->colors([
                        'danger' => fn ($state) => strtolower($state) === 'unpaid',
                        'warning' => fn ($state) => strtolower($state) === 'partial',
                        'success' => fn ($state) => strtolower($state) === 'paid',
                    ])
                    ->formatStateUsing(fn (string $state): string => match (strtolower($state)) {
                        'unpaid' => 'Belum Lunas',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                        default => $state,
                    }),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color('success'),
                TextColumn::make('received_by')
                    ->label('Penerima')
                    ->toggleable(),
            ])
            ->filters([
                DateFilterHelper::make('receipt_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('lihat_bukti')
                    ->label('Lihat Bukti')
                    ->icon('heroicon-o-photo')
                    ->color('success')
                    ->modalHeading('Bukti Faktur')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (\App\Models\GoodsReceipt $record) => view('components.faktur-images', [
                        'images' => is_array($record->faktur_image) ? $record->faktur_image : [$record->faktur_image]
                    ]))
                    ->visible(fn (\App\Models\GoodsReceipt $record) => !empty($record->faktur_image)),
                \Filament\Actions\Action::make('cetak_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\App\Models\GoodsReceipt $record) => route('print.document', ['type' => 'receipt', 'ids' => [$record->id]]))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('cetak_nota_massal')
                        ->label('Cetak Nota')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            if (empty($ids)) return;
                        })
                        ->url(fn (Collection $records) => route('print.document', ['type' => 'receipt', 'ids' => $records->pluck('id')->toArray()]))
                        ->openUrlInNewTab()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_daftar')
                    ->label('Cetak Daftar')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'penerimaan-barang',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ]);
    }
}
