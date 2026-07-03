<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameRackResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListStockOpnameRacks extends ListRecords
{
    protected static string $resource = StockOpnameRackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('unassigned_stocks')
                ->label('Barang Tanpa Rak')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->url(fn () => StockOpnameRackResource::getUrl('unassigned')),
            CreateAction::make(),
        ];
    }
}
