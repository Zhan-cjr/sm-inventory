<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->tel(),
                Select::make('manager_id')
                    ->options(\App\Models\User::pluck('name', 'id'))
                    ->searchable(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('latitude')
                    ->numeric()
                    ->required()
                    ->default(-6.9175)
                    ->step(0.000001),
                TextInput::make('longitude')
                    ->numeric()
                    ->required()
                    ->default(107.6191)
                    ->step(0.000001),
                ViewField::make('map')
                    ->view('filament.forms.components.map-picker')
                    ->columnSpanFull(),
            ]);
    }
}
