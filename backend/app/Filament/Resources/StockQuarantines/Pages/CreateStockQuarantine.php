<?php

namespace App\Filament\Resources\StockQuarantines\Pages;

use App\Filament\Resources\StockQuarantines\StockQuarantineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockQuarantine extends CreateRecord
{
    protected static string $resource = StockQuarantineResource::class;
    protected string $view = 'filament.resources.stock-quarantines.pages.pos-view';
    protected static ?string $title = 'Catat Barang Rusak (Pindah ke BS)';
}
