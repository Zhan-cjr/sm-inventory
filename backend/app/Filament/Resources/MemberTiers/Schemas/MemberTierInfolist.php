<?php

namespace App\Filament\Resources\MemberTiers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MemberTierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('organization.name')
                    ->label('Organization'),
                TextEntry::make('name'),
                TextEntry::make('min_points')
                    ->numeric(),
                TextEntry::make('discount_percent')
                    ->numeric(),
                TextEntry::make('color_hex')
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
