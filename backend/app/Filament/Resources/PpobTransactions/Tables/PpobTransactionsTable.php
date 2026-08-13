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
                    ->label('SKU Digiflazz')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Harga Modal')
                    ->money('IDR', locale: 'id')
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
                //
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
