<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->tooltip('Klik untuk salin NPWP'),
                TextColumn::make('contact_person')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('default_inventory_days')
                    ->label('Siklus Order')
                    ->formatStateUsing(fn ($state) => match ((int) ($state ?: 14)) {
                        7 => '7 Hr (1 Mg)',
                        14 => '14 Hr (2 Mg)',
                        21 => '21 Hr (3 Mg)',
                        30 => '30 Hr (1 Bln)',
                        default => ($state ?: 14) . ' Hari',
                    })
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('default_lead_time_days')
                    ->label('Lead Time')
                    ->formatStateUsing(fn ($state) => ($state ?: 3) . ' Hari')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                IconColumn::make('is_active')
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
                TernaryFilter::make('is_active')
                    ->label('Status Supplier')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-Aktif')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->products()->exists())
                    ->disabled(fn ($record) => $record->products()->exists()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->products()->exists()) {
                                    Notification::make()
                                        ->title('Gagal Hapus')
                                        ->body("Supplier '{$record->name}' tidak dapat dihapus karena masih memiliki barang terdaftar.")
                                        ->danger()
                                        ->send();
                                    $action->cancel();
                                    return;
                                }
                            }
                        }),
                ]),
            ]);
    }
}
