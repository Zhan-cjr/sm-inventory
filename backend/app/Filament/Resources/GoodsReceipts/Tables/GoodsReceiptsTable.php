<?php

namespace App\Filament\Resources\GoodsReceipts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
