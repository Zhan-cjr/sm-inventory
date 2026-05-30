<?php

namespace App\Filament\Resources\MemberTiers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MemberTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->label('Organisasi / Perusahaan')
                    ->required()
                    ->default(fn () => auth()->user()?->organization_id)
                    ->hidden(fn () => auth()->user()?->organization_id !== null), // Hide if user already belongs to org
                TextInput::make('name')
                    ->label('Nama Level')
                    ->placeholder('Misal: Silver')
                    ->required(),
                TextInput::make('min_points')
                    ->label('Minimal Poin')
                    ->helperText('Poin minimal yang harus dicapai pelanggan untuk mendapatkan level ini')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_percent')
                    ->label('Diskon Otomatis (%)')
                    ->helperText('Persentase diskon yang otomatis didapat member di level ini')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->default(0.0),
                \Filament\Forms\Components\ColorPicker::make('color_hex')
                    ->label('Warna Label (Opsional)')
                    ->helperText('Warna yang akan ditampilkan di POS Kasir'),
            ]);
    }
}
