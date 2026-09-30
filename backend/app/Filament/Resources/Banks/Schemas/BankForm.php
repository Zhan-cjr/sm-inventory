<?php

namespace App\Filament\Resources\Banks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Bank / Metode')
                    ->placeholder('Misal: MANDIRI, QRIS MANDIRI')
                    ->required(),
                TextInput::make('code')
                    ->label('Kode Bank / Channel')
                    ->placeholder('Misal: MDR, QRIS')
                    ->required(),
                Select::make('type')
                    ->label('Tipe / Jenis Channel')
                    ->options([
                        'EDC'      => 'EDC (Kartu Debit / Kredit)',
                        'QRIS'     => 'QRIS',
                        'TRANSFER' => 'Transfer Bank',
                    ])
                    ->default('EDC')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === 'QRIS') {
                            $set('min_transaction_amount', 20000);
                        } elseif ($state === 'EDC') {
                            $set('min_transaction_amount', 50000);
                        }
                    }),
                TextInput::make('min_transaction_amount')
                    ->label('Minimal Transaksi (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(50000)
                    ->required()
                    ->helperText('Nominal minimum pembayaran yang diizinkan untuk bank / channel ini.'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
