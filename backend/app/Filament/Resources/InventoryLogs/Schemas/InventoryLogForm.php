<?php

namespace App\Filament\Resources\InventoryLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InventoryLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('log_type')
                    ->required(),
                TextInput::make('quantity_change')
                    ->required()
                    ->numeric(),
                TextInput::make('reason_code'),
                TextInput::make('reference_doc_type'),
                Select::make('reference_doc_id')
                    ->options([]), // Extracted references
                Select::make('recorded_by')
                    ->options(\App\Models\User::pluck('name', 'id'))
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
