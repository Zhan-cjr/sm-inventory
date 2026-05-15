<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->organization_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->organization_id !== null)
                    ->dehydrated(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'SKU ini sudah digunakan oleh produk lain.',
                    ]),
                TextInput::make('barcode')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Barcode ini sudah digunakan oleh produk lain.',
                    ]),
                TextInput::make('name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('cost_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Toggle::make('is_taxable')
                    ->label('Kena PPN')
                    ->default(true)
                    ->required(),
                TextInput::make('unit_of_measure')
                    ->required()
                    ->default('pcs'),
                TextInput::make('reorder_point')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('reorder_qty')
                    ->required()
                    ->numeric()
                    ->default(50),
                TextInput::make('lead_time_days')
                    ->required()
                    ->numeric()
                    ->default(5),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('metadata'),
            ]);
    }
}
