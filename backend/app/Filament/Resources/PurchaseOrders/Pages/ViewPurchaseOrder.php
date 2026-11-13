<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ViewPurchaseOrder extends Page
{
    protected static string $resource = PurchaseOrderResource::class;

    protected string $view = 'filament.pages.edit-purchase-order';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public $record;

    public function mount($record): void
    {
        $this->record = PurchaseOrderResource::getModel()::findOrFail($record);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Lihat Pesanan Pembelian';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => PurchaseOrderResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}

