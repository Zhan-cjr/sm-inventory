<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'pointHistories';

    protected static ?string $title = 'Riwayat Perubahan Poin';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal & Waktu')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('before_points')
                    ->label('Poin Sebelum')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('points')
                    ->label('Perubahan Poin')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '') . $state),
                TextColumn::make('after_points')
                    ->label('Poin Sesudah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->label('Tipe Referensi')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'TRANSACTION' => 'info',
                        'ECOMMERCE_ORDER' => 'warning',
                        'ADJUSTMENT' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('reference_id')
                    ->label('ID Referensi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
