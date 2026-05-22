<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference_number')
                    ->required()
                    ->default('TRF-' . strtoupper(uniqid())),
                Select::make('from_branch_id')
                    ->relationship('fromBranch', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                    ->dehydrated()
                    ->searchable()
                    ->preload(),
                Select::make('to_branch_id')
                    ->relationship('toBranch', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'received' => 'Received',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->required()
                    ->disabled(), // Status diubah lewat action
                DatePicker::make('transfer_date')
                    ->required()
                    ->default(now()),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
