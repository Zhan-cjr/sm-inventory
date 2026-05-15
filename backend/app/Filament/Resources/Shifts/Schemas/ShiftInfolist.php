<?php

namespace App\Filament\Resources\Shifts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('branch.name')
                    ->label('Branch'),
                TextEntry::make('terminal.name')
                    ->label('Terminal'),
                TextEntry::make('start_time')
                    ->dateTime(),
                TextEntry::make('end_time')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('starting_cash')
                    ->numeric(),
                TextEntry::make('total_cash_sales')
                    ->numeric(),
                TextEntry::make('total_card_sales')
                    ->numeric(),
                TextEntry::make('actual_cash')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('difference')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('notes')
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
