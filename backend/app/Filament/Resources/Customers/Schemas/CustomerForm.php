<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
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
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->label('Kata Sandi (Isi untuk reset/buat baru)'),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('member_tier')
                    ->disabled()
                    ->dehydrated()
                    ->default('BRONZE'),
                TextInput::make('points')
                    ->disabled()
                    ->dehydrated()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
