<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseReturnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('organization_id'),
                TextEntry::make('branch_id')
                    ->placeholder('-'),
                TextEntry::make('supplier_id'),
                TextEntry::make('goods_receipt_id')
                    ->placeholder('-'),
                TextEntry::make('return_number'),
                TextEntry::make('return_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
