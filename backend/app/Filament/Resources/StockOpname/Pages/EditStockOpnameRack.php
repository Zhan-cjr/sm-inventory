<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameRackResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditStockOpnameRack extends EditRecord
{
    protected static string $resource = StockOpnameRackResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
