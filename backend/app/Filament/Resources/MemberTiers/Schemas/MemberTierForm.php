<?php

namespace App\Filament\Resources\MemberTiers\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MemberTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->label('Organisasi / Perusahaan')
                    ->required()
                    ->default(fn () => auth()->user()?->organization_id)
                    ->hidden(fn () => auth()->user()?->organization_id !== null), // Hide if user already belongs to org

                TextInput::make('name')
                    ->label('Nama Level Tier')
                    ->placeholder('Misal: Bronze Family, Silver Privilege, Gold Executive')
                    ->required(),

                TextInput::make('badge')
                    ->label('Label Badge (Company Profile)')
                    ->placeholder('Misal: MEMBER BARU, PALING POPULER, VIP SYARIAH')
                    ->nullable(),

                TextInput::make('min_points')
                    ->label('Minimal Poin')
                    ->helperText('Poin minimal yang harus dicapai pelanggan')
                    ->required()
                    ->numeric()
                    ->default(0),

                TextInput::make('min_spend_text')
                    ->label('Teks Kriteria Minimal Transaksi')
                    ->placeholder('Misal: Gratis Pendaftaran atau Transaksi > Rp 1.500.000 / bln')
                    ->nullable(),

                TextInput::make('discount_percent')
                    ->label('Diskon Otomatis (%)')
                    ->helperText('Persentase diskon yang otomatis didapat member di level ini')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->default(0.0),

                ColorPicker::make('color_hex')
                    ->label('Warna Label POS Kasir')
                    ->nullable(),

                TextInput::make('color_gradient')
                    ->label('Gradient Display CSS (Company Profile)')
                    ->placeholder('Misal: from-amber-700 via-amber-800 to-amber-950')
                    ->nullable(),

                TagsInput::make('perks')
                    ->label('Daftar Keuntungan / Benefit Member')
                    ->helperText('Ketik keuntungan lalu tekan ENTER (Misal: Poin belanja 2.5%, Gratis Ongkir, dll)')
                    ->placeholder('Tambah keuntungan baru...')
                    ->columnSpanFull(),
            ]);
    }
}
