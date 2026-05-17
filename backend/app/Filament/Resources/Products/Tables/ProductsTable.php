<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('stocks_sum_quantity_on_hand')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch')
                    ->label('Cabang')
                    ->relationship('stocks.branch', 'name')
                    ->hidden(fn () => auth()->user()->branch_id !== null)
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('kartu_stok')
                    ->label('Kartu Stok')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Kartu Stok: {$record->name}")
                    ->modalWidth('4xl')
                    ->modalFooterActions([])
                    ->modalContent(fn ($record) => view('filament.components.stock-card-livewire', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_to_branch')
                        ->label('Tetapkan ke Cabang')
                        ->icon('heroicon-o-building-storefront')
                        ->form([
                            \Filament\Forms\Components\Select::make('branch_id')
                                ->label('Pilih Cabang')
                                ->options(fn () => \App\Models\Branch::all()->pluck('name', 'id'))
                                ->searchable()
                                ->default(fn() => auth()->user()->branch_id)
                                ->disabled(fn() => auth()->user()->branch_id !== null)
                                ->dehydrated()
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->label('Stok Awal')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                \App\Models\Stock::updateOrCreate(
                                    [
                                        'branch_id' => $data['branch_id'],
                                        'product_id' => $record->id,
                                    ],
                                    [
                                        'quantity_on_hand' => $data['quantity'],
                                    ]
                                );
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
