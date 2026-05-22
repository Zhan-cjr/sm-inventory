<?php

namespace App\Filament\Resources\EcommerceSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EcommerceSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Organisasi/Toko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ecommerce_banner_title')
                    ->label('Judul Banner')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('ecommerce_announcement')
                    ->label('Pengumuman Running')
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // No create or delete actions to prevent messing with organization master records from here
            ]);
    }
}
