<?php

namespace App\Filament\Resources\SupplierDeductions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierDeductionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Cabang (Kosongkan jika Global)')
                    ->helperText('Jika dibiarkan kosong, potongan ini akan berlaku untuk semua cabang.'),
                \Filament\Forms\Components\Select::make('deduction_type')
                    ->options([
                        'PROMO_RAFAKSI' => 'Promo Rafaksi',
                        'LISTING_FEE' => 'Listing Fee',
                        'SEWA_DISPLAY' => 'Sewa Display',
                        'PURCHASE_RETURN' => 'Retur Pembelian',
                        'OTHER' => 'Lain-lain',
                    ])
                    ->required(),
                TextInput::make('reference_id')
                    ->label('ID Referensi (Promo/Retur)'),
                TextInput::make('amount')
                    ->required()
                    ->rupiah(),
                TextInput::make('claimed_amount')
                    ->required()
                    ->rupiah()
                    ->default(0)
                    ->readOnly(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'OPEN' => 'OPEN',
                        'PARTIAL' => 'PARTIAL',
                        'COMPLETED' => 'COMPLETED',
                    ])
                    ->required()
                    ->default('OPEN')
                    ->disabled(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
