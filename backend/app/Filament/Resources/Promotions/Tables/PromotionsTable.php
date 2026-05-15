<?php

namespace App\Filament\Resources\Promotions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organisasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Promosi')
                    ->searchable(),
                TextColumn::make('promo_type')
                    ->label('Jenis Promosi')
                    ->searchable(),
                TextColumn::make('discount_value')
                    ->label('Nilai Diskon')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_purchase_amount')
                    ->label('Minimal Pembelian')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('applicable_to')
                    ->label('Berlaku Untuk')
                    ->searchable(),
                TextColumn::make('valid_from')
                    ->label('Berlaku Dari')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('max_discount_per_transaction')
                    ->label('Maksimal Diskon')
                    ->money('IDR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
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
