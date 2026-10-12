<?php

namespace App\Filament\Resources\PpobTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PpobTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('transaction_id')
                    ->placeholder('-'),
                TextEntry::make('ref_id')
                    ->label('Ref ID'),
                TextEntry::make('customer_no')
                    ->label('No. Tujuan'),
                TextEntry::make('customer_name')
                    ->label('Nama Customer')
                    ->placeholder('-'),
                TextEntry::make('buyer_sku_code')
                    ->label('SKU Provider'),
                TextEntry::make('price')
                    ->label('Harga Modal')
                    ->money('IDR', locale: 'id')
                    ->state(fn ($record) => $record->modal_price),
                TextEntry::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->state(fn ($record) => $record->selling_price),
                TextEntry::make('profit')
                    ->label('Laba')
                    ->money('IDR', locale: 'id')
                    ->state(fn ($record) => $record->profit)
                    ->color('success')
                    ->weight('bold'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sukses' => 'success',
                        'Pending' => 'warning',
                        'Gagal' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('rc')
                    ->label('RC')
                    ->placeholder('-'),
                TextEntry::make('sn')
                    ->label('SN / Token')
                    ->placeholder('-'),
                TextEntry::make('message')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
