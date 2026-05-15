<?php

namespace App\Filament\Resources\PosSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PosSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('organization_id'),
                TextInput::make('key_name')
                    ->required(),
                TextInput::make('display_name')
                    ->required(),
                TextInput::make('shortcut_key')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
