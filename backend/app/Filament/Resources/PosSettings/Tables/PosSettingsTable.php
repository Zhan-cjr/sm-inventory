<?php

namespace App\Filament\Resources\PosSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PosSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Organisasi/Toko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode Unik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->label('Zona Waktu')
                    ->searchable(),
                IconColumn::make('allow_minus_stock')
                    ->label('Izinkan Stok Minus')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // No create/delete to prevent messing with organization master records
            ]);
    }
}
