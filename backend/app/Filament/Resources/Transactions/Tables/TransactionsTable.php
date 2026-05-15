<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('terminal.name')
                    ->label('Terminal/Kassa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->searchable(),
                TextColumn::make('transaction_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cashier.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('bank.name')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->searchable(),
                IconColumn::make('is_voided')
                    ->boolean(),
                TextColumn::make('void_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('voided_by')
                    ->label('Voided By')
                    ->formatStateUsing(fn ($state) => \App\Models\User::find($state)?->name ?? '-')
                    ->searchable(),
                TextColumn::make('sync_status')
                    ->searchable(),
                TextColumn::make('local_transaction_id')
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
