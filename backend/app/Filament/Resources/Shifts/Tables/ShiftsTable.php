<?php

namespace App\Filament\Resources\Shifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('terminal.name')
                    ->label('Terminal')
                    ->searchable(),
                TextColumn::make('shift_name')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label('Buka')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Tutup')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('starting_cash')
                    ->label('Modal Awal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_cash_sales')
                    ->label('Penjualan Tunai')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('actual_cash')
                    ->label('Uang Fisik')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('difference')
                    ->label('Selisih')
                    ->money('IDR')
                    ->color(fn($record) => $record->difference < 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => $state === 'OPEN' ? 'primary' : 'success'),
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
                \App\Filament\Filters\DateFilterHelper::make('created_at'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
