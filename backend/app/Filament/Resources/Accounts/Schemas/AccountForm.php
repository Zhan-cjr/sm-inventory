<?php

namespace App\Filament\Resources\Accounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->label('Perusahaan/Organisasi')
                    ->required(),
                TextInput::make('account_code')
                    ->label('Kode Akun')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Akun')
                    ->required(),
                Select::make('type')
                    ->label('Tipe Akun')
                    ->options([
                        'asset' => 'Aset / Harta',
                        'liability' => 'Kewajiban / Hutang',
                        'equity' => 'Modal / Ekuitas',
                        'revenue' => 'Pendapatan',
                        'expense' => 'Beban / Pengeluaran',
                    ])
                    ->required(),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
