<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockAdjustment extends Page
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
        return 'Lihat Koreksi Stok';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => StockAdjustmentResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function hasSaveButton(): bool
    {
        return false;
    }
}

