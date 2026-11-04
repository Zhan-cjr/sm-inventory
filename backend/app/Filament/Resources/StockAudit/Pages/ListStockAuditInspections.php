<?php

namespace App\Filament\Resources\StockAudit\Pages;

use App\Filament\Resources\StockAudit\StockAuditInspectionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListStockAuditInspections extends ListRecords
{
    protected static string $resource = StockAuditInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buka_pwa')
                ->label('Mulai Sidak di HP / PWA')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('primary')
                ->url('/mobile/stock-audit')
                ->openUrlInNewTab(),
        ];
    }
}

