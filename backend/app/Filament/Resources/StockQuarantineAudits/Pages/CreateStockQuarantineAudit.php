<?php

namespace App\Filament\Resources\StockQuarantineAudits\Pages;

use App\Filament\Resources\StockQuarantineAudits\StockQuarantineAuditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockQuarantineAudit extends CreateRecord
{
    protected static string $resource = StockQuarantineAuditResource::class;
    protected string $view = 'filament.resources.stock-quarantine-audits.pages.pos-view';
    protected static ?string $title = 'Pemeriksaan & Audit Keranjang BS';
}
