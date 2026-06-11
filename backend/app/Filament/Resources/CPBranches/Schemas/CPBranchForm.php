<?php

namespace App\Filament\Resources\CPBranches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CPBranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('open_hours'),
                TextInput::make('lat')
                    ->numeric(),
                TextInput::make('lng')
                    ->numeric(),
                Select::make('facilities')
                    ->label('Fasilitas Tersedia')
                    ->relationship('facilities', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
            ]);
    }
}
