<?php

namespace App\Filament\Resources\PpobTransactions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PpobTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('transaction_id'),
                TextInput::make('ref_id')
                    ->required(),
                TextInput::make('customer_no')
                    ->required(),
                TextInput::make('customer_name'),
                TextInput::make('buyer_sku_code')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('status')
                    ->required()
                    ->default('Pending'),
                TextInput::make('rc'),
                TextInput::make('sn'),
                Textarea::make('message')
                    ->columnSpanFull(),
                TextInput::make('raw_response'),
            ]);
    }
}
