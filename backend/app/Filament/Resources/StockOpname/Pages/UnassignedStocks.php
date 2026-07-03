<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameRackResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\Stock;
use App\Models\StockOpnameRack;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;

class UnassignedStocks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StockOpnameRackResource::class;

    protected string $view = 'filament.resources.stock-opname-rack-resource.pages.unassigned-stocks';

    protected static ?string $title = 'Barang Tanpa Rak';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->whereDoesntHave('racks')
                    ->when(Auth::user()->branch_id, function ($query, $branchId) {
                        return $query->where('branch_id', $branchId);
                    })
                    ->with(['product', 'branch'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label('Stok Sistem')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->actions([
                \Filament\Actions\Action::make('terapkan_rak')
                    ->label('Terapkan Rak')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('rack_id')
                            ->label('Pilih Rak')
                            ->options(function ($record) {
                                return StockOpnameRack::where('branch_id', $record->branch_id)
                                    ->pluck('rack_name', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Stock $record): void {
                        $record->racks()->attach($data['rack_id']);
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil ditambahkan ke rak')
                            ->success()
                            ->send();
                    })
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('terapkan_rak_massal')
                        ->label('Terapkan Rak (Massal)')
                        ->icon('heroicon-o-archive-box')
                        ->form([
                            Select::make('rack_id')
                                ->label('Pilih Rak')
                                ->options(function () {
                                    $branchId = Auth::user()->branch_id;
                                    $query = StockOpnameRack::query();
                                    if ($branchId) {
                                        $query->where('branch_id', $branchId);
                                    }
                                    return $query->pluck('rack_name', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $record->racks()->attach($data['rack_id']);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Barang terpilih berhasil ditambahkan ke rak')
                                ->success()
                                ->send();
                        })
                ]),
            ]);
    }
}
