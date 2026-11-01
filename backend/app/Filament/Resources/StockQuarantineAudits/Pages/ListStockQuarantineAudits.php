<?php

namespace App\Filament\Resources\StockQuarantineAudits\Pages;

use App\Filament\Resources\StockQuarantineAudits\StockQuarantineAuditResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockQuarantineAudits extends ListRecords
{
    protected static string $resource = StockQuarantineAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Mulai Audit Keranjang BS')
                ->icon('heroicon-o-plus'),
        ];
    }
}

