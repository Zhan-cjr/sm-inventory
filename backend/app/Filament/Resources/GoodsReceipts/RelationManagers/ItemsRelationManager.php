<?php

namespace App\Filament\Resources\GoodsReceipts\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('quantity_ordered')
                    ->label('Qty Pesanan')
                    ->numeric(),
                TextInput::make('quantity_received')
                    ->label('Qty Diterima')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('unit_price')
                    ->label('Harga Satuan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('discount_1')
                    ->label('Diskon 1 (%)')
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_2')
                    ->label('Diskon 2 (%)')
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_3')
                    ->label('Diskon 3 (%)')
                    ->numeric()
                    ->default(0),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('quantity_ordered')
                    ->label('Pesanan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quantity_received')
                    ->label('Diterima')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('discount_1')
                    ->label('Disc 1 (%)'),
                TextColumn::make('discount_2')
                    ->label('Disc 2 (%)'),
                TextColumn::make('discount_3')
                    ->label('Disc 3 (%)'),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
