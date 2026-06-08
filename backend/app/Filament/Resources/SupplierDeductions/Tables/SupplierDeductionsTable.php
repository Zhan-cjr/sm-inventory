<?php

namespace App\Filament\Resources\SupplierDeductions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierDeductionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->default('Global')
                    ->badge()
                    ->color(fn ($state) => $state === 'Global' ? 'success' : 'gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deduction_type')
                    ->label('Tipe')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Total Potongan')
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('claimed_amount')
                    ->label('Terpakai')
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'info',
                        'PARTIAL' => 'warning',
                        'COMPLETED' => 'success',
                        default => 'gray',
                    })
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
