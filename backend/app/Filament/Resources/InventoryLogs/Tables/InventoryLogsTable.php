<?php

namespace App\Filament\Resources\InventoryLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('log_type')
                    ->searchable(),
                TextColumn::make('quantity_change')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reason_code')
                    ->searchable(),
                TextColumn::make('reference_doc_type')
                    ->searchable(),
                TextColumn::make('reference_doc_id')
                    ->searchable(),
                TextColumn::make('recorded_by')
                    ->label('Recorded By')
                    ->formatStateUsing(fn ($state) => \App\Models\User::find($state)?->name ?? '-')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
