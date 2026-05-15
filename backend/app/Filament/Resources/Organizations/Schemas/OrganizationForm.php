<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('timezone')
                    ->required()
                    ->default('Asia/Jakarta'),
                TextInput::make('currency_code')
                    ->required()
                    ->default('IDR'),
            ]);
    }
}
