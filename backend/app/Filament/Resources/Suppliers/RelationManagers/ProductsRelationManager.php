<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Daftar Barang';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // 
            ])
            ->actions([
                Action::make('pindah_pemasok')
                    ->label('Pindah Pemasok')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('new_supplier_id')
                            ->label('Pilih Pemasok Tujuan')
                            ->options(function () {
                                return \App\Models\Supplier::pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update(['supplier_id' => $data['new_supplier_id']]);
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil pindah pemasok')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('pindah_pemasok_bulk')
                        ->label('Pindah Pemasok (Terpilih)')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\Select::make('new_supplier_id')
                                ->label('Pilih Pemasok Tujuan')
                                ->options(function () {
                                    return \App\Models\Supplier::pluck('name', 'id');
                                })
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['supplier_id' => $data['new_supplier_id']]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil pindah pemasok untuk barang terpilih')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
