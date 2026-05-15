<?php

namespace App\Filament\Resources\Shifts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                    ->dehydrated(),
                Select::make('terminal_id')
                    ->relationship('terminal', 'name')
                    ->required(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time'),
                TextInput::make('starting_cash')
                    ->required()
                    ->numeric(),
                TextInput::make('total_cash_sales')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_card_sales')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('actual_cash')
                    ->numeric(),
                TextInput::make('difference')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('OPEN'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
