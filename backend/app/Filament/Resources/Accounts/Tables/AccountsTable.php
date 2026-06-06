<?php

namespace App\Filament\Resources\Accounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_code')
                    ->label('Kode Akun')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe Akun')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'asset' => 'Aset',
                        'liability' => 'Kewajiban',
                        'equity' => 'Ekuitas',
                        'revenue' => 'Pendapatan',
                        'expense' => 'Beban',
                        default => $state,
                    })
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
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
