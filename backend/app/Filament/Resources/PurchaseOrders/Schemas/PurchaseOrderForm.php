<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                    ->dehydrated()
                    ->searchable()
                    ->preload(),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('po_number')
                    ->required(),
                DatePicker::make('po_date')
                    ->required(),
                DatePicker::make('expected_delivery_date'),
                TextInput::make('status')
                    ->required()
                    ->default('DRAFT'),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('include_tax')
                    ->label('Include PPN')
                    ->default(true)
                    ->required(),
                TextInput::make('tax_amount')
                    ->label('Nominal PPN')
                    ->numeric()
                    ->default(0.0),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->required(),
            ]);
    }
}
