<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameSessionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListStockOpnameSessions extends ListRecords
{
    protected static string $resource = StockOpnameSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Buat Sesi Opname')];
    }
}
