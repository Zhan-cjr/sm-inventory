<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TransactionForm
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
                    ->dehydrated(),
                Select::make('terminal_id')
                    ->relationship('terminal', 'name')
                    ->label('Terminal/Kassa'),
                TextInput::make('transaction_type')
                    ->required(),
                DateTimePicker::make('transaction_date')
                    ->required(),
                Select::make('cashier_id')
                    ->relationship('cashier', 'name')
                    ->required(),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('Rp'),
                TextInput::make('final_amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('payment_method'),
                Select::make('bank_id')
                    ->relationship('bank', 'name')
                    ->label('Bank (Non-Tunai)'),
                TextInput::make('received_amount')
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('change_amount')
                    ->numeric()
                    ->prefix('Rp'),
                Toggle::make('is_voided')
                    ->required(),
                Textarea::make('void_reason')
                    ->columnSpanFull(),
                DateTimePicker::make('void_date'),
                Select::make('voided_by')
                    ->options(\App\Models\User::pluck('name', 'id')),
                TextInput::make('sync_status')
                    ->required()
                    ->default('PENDING'),
                TextInput::make('local_transaction_id'),
            ]);
    }
}
