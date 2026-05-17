<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class CreateStockAdjustment extends Page
{
    protected static string $resource = StockAdjustmentResource::class;

    protected string $view = 'filament.pages.create-stock-adjustment';

    public function getTitle(): string | Htmlable
    {
        return 'Buat Koreksi Stok';
    }

    public function hasSaveButton(): bool
    {
        return false;
    }
}
