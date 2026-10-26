<?php

namespace App\Filament\Resources\StockQuarantines\Pages;

use App\Filament\Resources\StockQuarantines\StockQuarantineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockQuarantines extends ListRecords
{
    protected static string $resource = StockQuarantineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Barang Rusak (Pindah ke BS)')
                ->icon('heroicon-o-plus-circle')
                ->color('danger'),
        ];
    }
}
