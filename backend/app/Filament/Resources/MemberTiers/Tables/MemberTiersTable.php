<?php

namespace App\Filament\Resources\MemberTiers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MemberTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Level')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->color_hex ? \Filament\Support\Colors\Color::hex($record->color_hex) : 'primary'),
                TextColumn::make('min_points')
                    ->label('Minimal Poin')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_percent')
                    ->label('Diskon (%)')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
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
