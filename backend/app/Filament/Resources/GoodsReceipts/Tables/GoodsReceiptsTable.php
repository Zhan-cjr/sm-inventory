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
            ]);
    }
}
