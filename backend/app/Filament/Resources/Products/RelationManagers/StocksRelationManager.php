<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->required()
                    ->disabled(fn ($record) => $record !== null), // Only allow choosing branch on create/attach
                TextInput::make('cost_price')
                    ->label('Harga Beli Cabang')
                    ->helperText('Kosongkan untuk menggunakan harga default produk')
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('selling_price')
                    ->label('Harga Jual Cabang')
                    ->helperText('Kosongkan untuk menggunakan harga default produk')
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('quantity_on_hand')
                    ->label('Stok Saat Ini')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('min_qty')
                    ->label('Min. Stok')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('max_qty')
                    ->label('Max. Stok')
                    ->required()
                    ->numeric()
                    ->default(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('branch.name')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->label('Harga Beli (Cabang)')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder(fn ($record) => $record && $record->product ? 'Default: Rp ' . number_format($record->product->cost_price, 0, ',', '.') : 'Rp 0'),
                TextColumn::make('selling_price')
                    ->label('Harga Jual (Cabang)')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder(fn ($record) => $record && $record->product ? 'Default: Rp ' . number_format($record->product->selling_price, 0, ',', '.') : 'Rp 0'),
                TextColumn::make('quantity_on_hand')
                    ->label('Stok')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_qty')
                    ->label('Min')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_qty')
                    ->label('Max')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Stok Cabang'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Hapus dari Cabang'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
