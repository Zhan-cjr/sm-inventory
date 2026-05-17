<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\StockAdjustment;

class EditStockAdjustment extends Page
{
    protected static string $resource = StockAdjustmentResource::class;

    protected string $view = 'filament.pages.edit-stock-adjustment';

    public $record;

    public function mount(int | string $record): void
    {
        $this->record = StockAdjustment::findOrFail($record);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Edit Koreksi Stok';
    }

    public function hasSaveButton(): bool
    {
        return false;
    }
}
