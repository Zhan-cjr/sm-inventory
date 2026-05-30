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
                TextEntry::make('ref_id'),
                TextEntry::make('customer_no'),
                TextEntry::make('customer_name')
                    ->placeholder('-'),
                TextEntry::make('buyer_sku_code'),
                TextEntry::make('price')
                    ->money(),
                TextEntry::make('status'),
                TextEntry::make('rc')
                    ->placeholder('-'),
                TextEntry::make('sn')
                    ->placeholder('-'),
                TextEntry::make('message')
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
