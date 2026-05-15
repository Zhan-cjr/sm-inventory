<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('purchase_order_id')
                    ->relationship('purchaseOrder', 'id'),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                    ->dehydrated()
                    ->searchable()
                    ->preload(),
                TextInput::make('receipt_number')
                    ->required(),
                DateTimePicker::make('receipt_date')
                    ->required(),
                TextInput::make('received_by')
                    ->required(),
                TextInput::make('faktur_supplier'),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Toggle::make('include_tax')
                    ->label('Include PPN')
                    ->default(true)
                    ->required(),
                TextInput::make('tax_amount')
                    ->label('Nominal PPN')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('RECEIVED'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
