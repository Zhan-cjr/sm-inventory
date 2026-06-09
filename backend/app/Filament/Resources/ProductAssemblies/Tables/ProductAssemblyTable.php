<?php

namespace App\Filament\Resources\ProductAssemblies\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductAssemblyTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Nama Paket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('assemblies_count')
                    ->label('Jumlah Komponen')
                    ->counts('assemblies')
                    ->badge()
                    ->color('info'),

                TextColumn::make('cost_price')
                    ->label('HPP (tanpa PPN)')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('cost_price_tax')
                    ->label('HPP (+PPN)')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('harga_jual_1')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('margin_gol_1')
                    ->label('Margin %')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 20 => 'success',
                        $state >= 10 => 'warning',
                        default      => 'danger',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->recordAction('edit')
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }
}
